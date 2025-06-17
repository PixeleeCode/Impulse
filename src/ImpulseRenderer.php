<?php

namespace Impulse;

use Impulse\Attributes\Renderer;
use Impulse\Interfaces\TemplateRendererInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ImpulseRenderer
{
    private static ?TemplateRendererInterface $renderer = null;

    public static function get(): ?TemplateRendererInterface
    {
        if (self::$renderer !== null) {
            return self::$renderer;
        }

        $configPath = getcwd() . '/../.impulse/config.json';
        if (!file_exists($configPath)) {
            return null;
        }

        try {
            $config = json_decode(
                file_get_contents($configPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return null;
        }

        $engine = null;
        if (!empty($config['template_engine'])) {
            $engine = $config['template_engine'];
        }

        if($engine === null) {
            return null;
        }

        $resourcesPath = 'resources/views';
        if (!empty($config['template_path'])) {
            $resourcesPath = $config['template_path'];
        }

        $absolutePath = getcwd() . '/../' . ltrim($resourcesPath, '/');
        if (!is_dir($absolutePath) && !mkdir($absolutePath, 0755, true) && !is_dir($absolutePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $absolutePath));
        }

        $baseNamespace = 'Impulse\\Rendering\\';
        $directory = __DIR__ . '/Rendering';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Renderer.php')) {
                continue;
            }

            $className = $baseNamespace . $file->getBasename('.php');
            require_once $file->getPathname();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);
            foreach ($reflection->getAttributes(Renderer::class) as $attribute) {
                /** @var Renderer $attr */
                $attr = $attribute->newInstance();
                if (strtolower($attr->name) === strtolower($engine)) {
                    $instance = new $className($absolutePath);
                    if (!$instance instanceof TemplateRendererInterface) {
                        continue;
                    }

                    return self::$renderer = $instance;
                }
            }
        }

        return null;
    }
}
