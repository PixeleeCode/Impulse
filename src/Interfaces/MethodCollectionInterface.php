<?php

namespace Impulse\Interfaces;

interface MethodCollectionInterface
{
    public function register(string $name, callable $method): self;
    public function call(string $name, array $arguments = []): mixed;
    public function exists(string $name): bool;
}
