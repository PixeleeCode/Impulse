<?php

namespace Impulse;

use Impulse\Collections\Collection\ComponentCollection;
use Impulse\Core\Component;

/**
 * Factory pour faciliter la création et l'utilisation des composants Impulse
 */
class ImpulseFactory
{
    /**
     * Compteur d'instances par nom de classe
     * @var array<string, int>
     */
    private static array $classInstanceCounts = [];

    /**
     * Collection des instances créées pour éviter les doublons
     */
    private static ComponentCollection $instances;

    /**
     * Initialise la collection d'instances
     */
    private static function initializeInstances(): void
    {
        if (!isset(self::$instances)) {
            self::$instances = new ComponentCollection();
        }
    }

    /**
     * Crée une nouvelle instance d'un composant avec un ID généré automatiquement
     *
     * @template T of Component
     * @param class-string<T> $componentClass
     * @param array<int, mixed> $defaults
     * @param string|null $id
     * @return Component Instance du composant
     */
    public static function create(string $componentClass, array $defaults = [], ?string $id = null): Component
    {
        self::initializeInstances();

        if (!class_exists($componentClass)) {
            throw new \InvalidArgumentException("La classe de composant '$componentClass' n'existe pas");
        }

        if (!is_subclass_of($componentClass, Component::class)) {
            throw new \InvalidArgumentException("La classe '$componentClass' n'est pas un composant valide");
        }

        if (!$id) {
            // Générer un ID incrémental basé sur le nom du composant
            $reflection = new \ReflectionClass($componentClass);
            $baseId = strtolower($reflection->getShortName());

            if (!isset(self::$classInstanceCounts[$baseId])) {
                self::$classInstanceCounts[$baseId] = 1;
            } else {
                self::$classInstanceCounts[$baseId]++;
            }

            $id = $baseId . '_' . self::$classInstanceCounts[$baseId];
        }

        $component = new $componentClass($id, $defaults);

        self::$instances->cache($id, $component);

        return $component;
    }

    /**
     * Récupère toutes les instances créées
     */
    public static function getInstances(): ComponentCollection
    {
        self::initializeInstances();
        return self::$instances;
    }

    /**
     * Nettoie toutes les instances
     */
    public static function cleanup(): void
    {
        self::initializeInstances();
        self::$instances->clear();
    }
}
