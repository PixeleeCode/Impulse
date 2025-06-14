<?php

namespace Impulse\Interfaces;

interface StateCollectionInterface
{
    public function getValue(string $name): mixed;
    public function setValue(string $name, mixed $value): void;
}
