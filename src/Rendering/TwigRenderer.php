<?php

namespace Impulse\Rendering;

use Impulse\Attributes\Renderer;
use Impulse\Interfaces\TemplateRendererInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

#[Renderer(
    name: 'twig',
    bundle: 'twig/twig:^3.0'
)]
class TwigRenderer implements TemplateRendererInterface
{
    private Environment $twig;

    public function __construct(string $viewsPath = '')
    {
        $loader = new FilesystemLoader($viewsPath);
        $this->twig = new Environment($loader, [
            'cache' => dirname(__DIR__, 2) . '/var/storage/cache/twig',
            'auto_reload' => true,
        ]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(string $template, array $data = []): string
    {
        $template = strtolower($template);
        if (!str_ends_with($template, '.twig')) {
            $template .= '.twig';
        }

        return $this->twig->render($template, $data);
    }
}
