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

        Config::load();

        $engine = Config::get('template_engine');
        if(!$engine) {
            return null;
        }

        $resourcesPath = Config::get('template_path', 'resources/views');
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
