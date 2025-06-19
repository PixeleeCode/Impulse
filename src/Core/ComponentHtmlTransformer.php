<?php

namespace Impulse\Core;

use Impulse\ImpulseFactory;

class ComponentHtmlTransformer
{
    /**
     * @throws \JsonException
     * @throws \ReflectionException
     * @throws \DOMException
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

    /**
     * @throws \ReflectionException
     */
    private function getComponentTags(): array
    {
        $components = [];
        $namespaces = Config::get('component_namespaces', []);

        foreach ($namespaces as $namespace) {
            $directory = namespace_to_path($namespace);
            if (!is_dir($directory)) {
                continue;
            }

            foreach (scandir($directory) as $file) {
                if (!str_ends_with($file, '.php')) {
                    continue;
                }

                $className = $namespace . basename($file, '.php');
                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new \ReflectionClass($className);
                if ($reflection->isInstantiable()) {
                    $instance = $reflection->newInstanceWithoutConstructor();
                    $tagName = property_exists($instance, 'tagName') && is_string($instance->tagName)
                        ? $instance->tagName
                        : strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $reflection->getShortName()));
                    $components[$tagName] = $className;
                }
            }
        }

        return $components;
    }
}
