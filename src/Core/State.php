<?php

namespace Impulse\Core;

use Impulse\Interfaces\StateInterface;

/**
 * Gère un état réactif pour un composant
 */
class State implements StateInterface
{
    private mixed $value;
    private bool $protected;

    /**
     * Crée un nouvel état
     */
    public function __construct(mixed $defaultValue = null, bool $protected = false)
    {
        $this->value = $defaultValue;
        $this->protected = $protected;
    }

    /**
     * Récupère la valeur actuelle
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Définit une nouvelle valeur
     */
    public function set(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Retourne si la valeur est protégée
     */
    public function isProtected(): bool
    {
        return $this->protected;
    }

    /**
     * Retourne la valeur pour le DOM : hash si protégé, sinon valeur réelle
     */
    public function getForDom(): mixed
    {
        if ($this->isProtected()) {
            return $this->hash($this->value);
        }

        return $this->value;
    }

    /**
     * Hash une valeur (pour affichage DOM uniquement)
     */
    private function hash(mixed $value): string
    {
        return hash('sha256', serialize($value));
    }

    /**
     * Permet d'utiliser l'objet directement comme une chaîne
     */
    public function __toString(): string
    {
        $value = $this->get();

        if (is_array($value) || is_object($value)) {
            try {
                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?: '';
            } catch (\JsonException) {
                return '';
            }
        }

        return (string) $value;
    }
}
