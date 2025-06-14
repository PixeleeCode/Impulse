<?php

namespace Impulse\Collections\Collection;

use Impulse\Collections\Collection;
use Impulse\Core\Component;

/**
 * Collection pour le cache des composants
 * @extends Collection<Component>
 */
class ComponentCollection extends Collection
{
    /**
     * Cache un composant
     */
    public function cache(string $id, Component $component): self
    {
        return $this->set($id, $component);
    }

    /**
     * Récupère un composant du cache
     */
    public function getCached(string $id): ?Component
    {
        return $this->get($id);
    }

    /**
     * Vérifie si un composant est en cache
     */
    public function isCached(string $id): bool
    {
        return $this->has($id);
    }
}
