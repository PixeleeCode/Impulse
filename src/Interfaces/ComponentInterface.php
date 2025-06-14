<?php

namespace Impulse\Interfaces;

interface ComponentInterface
{
    public function setup(): void;
    public function render(): string;
    public function getId(): string;
    /** @return array<int|string, mixed> */
    public function getStates(): array;
    public function onAfterAction(): void;
    public function onBeforeAction(?string $method = null, array $args = []): void;
}
