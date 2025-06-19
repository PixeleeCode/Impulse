<?php

namespace Impulse\Collections\Collection;

use Impulse\Collections\Collection;
use Impulse\Interfaces\StateCollectionInterface;
use Impulse\Core\State;
use Impulse\Core\Component;

/**
 * Collection spécialisée pour le cache d'état
 * @extends Collection<State>
 */
class StateCollection extends Collection implements StateCollectionInterface
{
    private ?Component $component = null;

    /**
     * Crée ou récupère un état
     */
    public function getOrCreate(string $name, mixed $defaultValue, bool $protected = false): State
    {
        if (!$this->has($name)) {
            $state = new State($defaultValue, $protected);
            $this->set($name, $state);
        }

        return $this->get($name);
    }

    /**
     * Récupère la valeur d'un état
     */
    public function getValue(string $name): mixed
    {
        $state = $this->get($name);
        return $state ? $state->get() : null;
    }

    /**
     * Définit la valeur d'un état
     */
    public function setValue(string $name, mixed $value): void
    {
        $state = $this->get($name);
        if ($state) {
            $oldValue = $state->get();
            $state->set($value);

            if ($this->component) {
                $watchers = $this->component->getWatchers();
                if ($watchers->has($name)) {
                    foreach ($watchers->get($name) as $callback) {
                        $callback($value, $oldValue);
                    }
                }
            }
        }
    }

    public function setComponent(Component $component): void
    {
        $this->component = $component;
    }
}
