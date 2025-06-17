<?php

namespace Impulse\Rendering;

use Illuminate\View\Engines\CompilerEngine;
use Impulse\Attributes\Renderer;
use Impulse\Interfaces\TemplateRendererInterface;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;

#[Renderer(
    name: 'blade',
    bundle: 'illuminate/view'
)]
class BladeRenderer implements TemplateRendererInterface
{
    private Factory $factory;

    public function __construct(string $viewsPath = '')
    {
        $cachePath = dirname(__DIR__, 2) . '/var/storage/cache/blade';
        if (!is_dir($cachePath) && !mkdir($cachePath, 0755, true) && !is_dir($cachePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $cachePath));
        }

        $filesystem = new Filesystem();
        $resolver = new EngineResolver();
        $compiler = new BladeCompiler($filesystem, $cachePath);
        $resolver->register('blade', fn () => new CompilerEngine($compiler));

        $container = new Container();
        $finder = new FileViewFinder($filesystem, [$viewsPath]);
        $this->factory = new Factory($resolver, $finder, new Dispatcher($container));
    }

    public function render(string $template, array $data = []): string
    {
        $template = str_replace(['.blade.php', '.php', '/'], '', $template);
        return $this->factory->make($template, $data)->render();
    }
}
