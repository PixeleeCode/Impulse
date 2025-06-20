<?php

namespace Impulse\Core;

use Composer\Autoload\ClassLoader;
use Impulse\ImpulseFactory;
use ScssPhp\ScssPhp\Exception\SassException;

class ComponentHtmlTransformer
{
    /**
     * @throws \JsonException
     * @throws \ReflectionException
     * @throws \DOMException|SassException
     */
    public function process(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        static $globalCounters = [];

        do {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?><!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);

            $componentTags = $this->getComponentTags();
            $found = false;

            foreach ($componentTags as $tagName => $className) {
                foreach ($xpath->query("//$tagName") as $node) {
                    if (!$node instanceof \DOMElement) {
                        continue;
                    }

                    $props = [];
                    foreach ($node->attributes as $attr) {
                        $props[$attr->name] = $attr->value;
                    }

                    $componentBase = strtolower((new \ReflectionClass($className))->getShortName());
                    $globalCounters[$componentBase] = ($globalCounters[$componentBase] ?? 0) + 1;
                    $id = $componentBase . '_imbrication_' . $globalCounters[$componentBase];

                    $slots = [];
                    foreach ($node->getElementsByTagName('slot') as $slotElement) {
                        $slotName = $slotElement->getAttribute('name') ?: '__slot';
                        $slots[$slotName] = $this->innerHTML($slotElement);
                    }

                    if (empty($slots['__slot'])) {
                        $raw = '';
                        foreach ($node->childNodes as $child) {
                            $raw .= $dom->saveHTML($child);
                        }
                        $slots['__slot'] = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }

                    $props['__slot'] = $slots['__slot'] ?? '';

                    $instance = ImpulseFactory::create($className, $props, $id);

                    foreach ($slots as $name => $content) {
                        if ($name !== '__slot') {
                            $instance->setSlot($name, $content);
                        }
                    }

                    $rendered = $instance->render();

                    $encodedSlot = base64_encode($props['__slot']);
                    $rendered = preg_replace_callback(
                        '/(<div[^>]+data-impulse-id="[^"]+")/',
                        static fn($matches) => $matches[1] . ' data-impulse-slot="' . $encodedSlot . '"',
                        $rendered
                    );

                    $tmpDom = new \DOMDocument();
                    libxml_use_internal_errors(true);
                    $tmpDom->loadHTML('<?xml encoding="utf-8" ?><!DOCTYPE html><html><body>' . $rendered . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    libxml_clear_errors();

                    $body = $tmpDom->getElementsByTagName('body')->item(0);
                    $importedFragment = $dom->createDocumentFragment();
                    foreach (iterator_to_array($body?->childNodes) as $child) {
                        $imported = $dom->importNode($child, true);
                        $importedFragment->appendChild($imported);
                    }

                    $node->parentNode->replaceChild($importedFragment, $node);
                    $found = true;
                }
            }

            // Mise à jour du HTML à la fin de chaque tour
            $body = $dom->getElementsByTagName('body')->item(0);
            $innerHTML = '';
            foreach ($body?->childNodes ?? [] as $child) {
                $innerHTML .= $dom->saveHTML($child);
            }

            $html = html_entity_decode($innerHTML, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        } while ($found);

        return $html;
    }

    private function innerHTML(\DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    private function getComponentTags(): array
    {
        $components = [];
        $namespaces = Config::get('component_namespaces', []);

        foreach ($namespaces as $namespace) {
            // Assurez-vous que le namespace se termine par un séparateur
            $namespace = rtrim($namespace, '\\') . '\\';

            $directory = $this->namespaceToPath($namespace);
            if (!is_dir($directory)) {
                continue;
            }

            // Filtrer directement les fichiers PHP
            $phpFiles = glob($directory . '/*.php');
            if ($phpFiles === false) {
                continue;
            }

            foreach ($phpFiles as $filePath) {
                $file = basename($filePath);
                $className = $namespace . basename($file, '.php');

                try {
                    if (!class_exists($className)) {
                        continue;
                    }

                    $reflection = new \ReflectionClass($className);

                    // Vérifiez si c'est un composant valide (exemple)
                    if (!$reflection->isSubclassOf(Component::class)) {
                        continue;
                    }

                    if (!$reflection->isInstantiable()) {
                        continue;
                    }

                    // Approche plus sûre pour accéder aux propriétés
                    try {
                        $instance = $reflection->newInstanceWithoutConstructor();

                        // Vérifiez que tagName existe et est accessible
                        $tagNameProperty = $reflection->hasProperty('tagName') ? $reflection->getProperty('tagName') : null;

                        if ($tagNameProperty && $tagNameProperty->isPublic()) {
                            $tagName = $tagNameProperty->getValue($instance);
                            if (!is_string($tagName)) {
                                $tagName = null;
                            }
                        } else {
                            $tagName = null;
                        }

                        // Utilise le nom de la classe si tagName n'est pas disponible
                        if ($tagName === null) {
                            $tagName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $reflection->getShortName()));
                        }

                        $components[$tagName] = $className;
                    } catch (\Throwable $e) {
                        // Log l'erreur ou continue silencieusement
                        continue;
                    }
                } catch (\Throwable $e) {
                    // Gestion des erreurs de réflexion
                    continue;
                }
            }
        }

        return $components;
    }

    /**
     * Convertit un namespace en chemin de fichier
     */
    private function namespaceToPath(string $namespace): ?string
    {
        $autoloaderFiles = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../autoload.php', // Si ce package est dans vendor
            __DIR__ . '/../../../../autoload.php', // Si plus profond
        ];

        $loader = null;
        if (!isset($loader) || !$loader instanceof ClassLoader) {
            foreach ($autoloaderFiles as $file) {
                if (file_exists($file)) {
                    $loader = require $file;
                    break;
                }
            }
        }

        if (!$loader) {
            return null;
        }

        $prefixesPsr4 = $loader->getPrefixesPsr4();

        foreach ($prefixesPsr4 as $prefix => $dirs) {
            if (str_starts_with($namespace, $prefix)) {
                $relative = str_replace('\\', '/', substr($namespace, strlen($prefix)));
                return rtrim($dirs[0], '/') . '/' . $relative;
            }
        }

        return null;
    }
}
