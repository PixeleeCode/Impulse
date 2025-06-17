<?php

namespace Impulse\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Renderer
{
    public function __construct(
        public string $name,
        public ?string $bundle = null
    ) {}
}
