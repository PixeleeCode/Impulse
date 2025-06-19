<?php

namespace Impulse\Core;

use Impulse\Collections\Collection\ComponentCollection;

class ComponentResolver
{
    /**
     * Cache des composants déjà instanciés
     */
    private static ComponentCollection $componentCache;

    /**
     * Préfixe de namespace pour les composants
     */
    private static string $componentNamespace = 'Impulse\\Components\\';

    /**
     * Initialise les collections statiques
     */
    private static function initializeCollections(): void
    {
        if (!isset(self::$componentCache)) {
            self::$componentCache = new ComponentCollection();
        }
    }

    /**
     * Résout un composant à partir de son ID
     */
    public static function resolve(string $id): ?Component
    {
        self::initializeCollections();

        if (self::$componentCache->isCached($id)) {
            error_log("Composant trouvé dans le cache: $id");
            return self::$componentCache->getCached($id);
        }

        // Décomposer l'ID pour extraire le type de composant
        if (!preg_match('/^([a-z]+)_/', $id, $matches)) {
            error_log("Format d'ID invalide: $id");
            return null;
        }

        $prefix = $matches[1];

        // Déterminer le nom de la classe à partir du préfixe
        $className = self::getClassNameFromPrefix($prefix);
        if ($className === null) {
            error_log("Aucune classe associée au préfixe: $prefix");
            return null;
        }

        $componentClass = self::$componentNamespace . $className;

        if (!class_exists($componentClass)) {
            error_log("Classe non trouvée: $componentClass");
            return null;
        }

        try {
            $defaults = [];
            if (func_num_args() > 1 && is_array(func_get_arg(1))) {
                $defaults = func_get_arg(1);
            }

            $component = new $componentClass($id, $defaults);
            self::$componentCache->cache($id, $component);
            return $component;
        } catch (\Throwable $e) {
            error_log("Erreur lors de l'instanciation du composant $componentClass : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Détermine le nom de la classe à partir du préfixe d'ID
     */
    private static function getClassNameFromPrefix(string $prefix): ?string
    {
        self::initializeCollections();

        $guessedClassName = ucfirst($prefix);
        $fullClassName = self::$componentNamespace . $guessedClassName;

        if (class_exists($fullClassName)) {
            return $guessedClassName;
        }

        return null;
    }

    /**
     * Vide le cache des composants
     */
    public static function clearCache(): void
    {
        self::initializeCollections();
        self::$componentCache->clear();
    }
}
