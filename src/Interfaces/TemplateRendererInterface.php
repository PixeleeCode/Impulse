<?php

namespace Impulse\Interfaces;

interface TemplateRendererInterface
{
    public function render(string $template, array $data = []): string;
}
