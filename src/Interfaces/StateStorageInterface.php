<?php

namespace Impulse\Interfaces;

interface StateStorageInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): void;
}
