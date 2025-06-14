<?php

namespace Impulse\Collections\Collection;

use Impulse\Collections\Collection;
use Impulse\Interfaces\MethodCollectionInterface;

/**
 * Collection spécialisée pour les méthodes de composants
 */
class MethodCollection extends Collection implements MethodCollectionInterface
{
    /**
     * Enregistre une méthode
     */
    public function register(string $name, callable $method): self
    {
        return $this->set($name, $method);
    }

    /**
     * Appelle une méthode avec des arguments
     *
     * @param array<int, mixed> $arguments
     */
    public function call(string $name, array $arguments = []): mixed
    {
        $method = $this->get($name);
        if ($method === null) {
            throw new \RuntimeException("La méthode '$name' n'existe pas sur ce composant");
        }

        return $method(...$arguments);
    }

    /**
     * Vérifie si une méthode existe
     */
    public function exists(string $name): bool
    {
        return $this->has($name);
    }
}
