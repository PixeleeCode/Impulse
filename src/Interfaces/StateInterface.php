<?php

namespace Impulse\Interfaces;

interface StateInterface
{
    public function get(): mixed;
    public function set(mixed $value): void;
}
