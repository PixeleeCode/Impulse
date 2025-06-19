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
     * Liste des namespaces enregistrés automatiquement
     */
    private static array $namespaces = [];

    public static function all(): array
    {
        self::initializeCollections();

        return self::$componentCache->toArray();
    }

    /**
     * Initialise les collections statiques
     */
    private static function initializeCollections(): void
    {
        if (!isset(self::$componentCache)) {
            Config::load();
            self::$componentCache = new ComponentCollection();
        }

        $names = Config::get('component_namespaces', []);
        foreach ($names as $namespace) {
            if (!in_array($namespace, self::$namespaces, true)) {
                self::$namespaces[] = $namespace;
            }
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

        if (!preg_match('/^([a-z]+)_/', $id, $matches)) {
            error_log("Format d'ID invalide: $id");
            return null;
        }

        $prefix = $matches[1];

        $className = self::getClassNameFromPrefix($prefix);
        if ($className === null) {
            error_log("Aucune classe associée au préfixe: $prefix");
            return null;
        }

        $componentClass = null;
        foreach (self::$namespaces as $namespace) {
            $candidate = $namespace . $className;
            if (class_exists($candidate)) {
                $componentClass = $candidate;
                break;
            }
        }

        if (!$componentClass) {
            error_log("Classe non trouvée pour le préfixe: $prefix");
            return null;
        }

        try {
            $defaults = [];
            if (func_num_args() > 1 && is_array(func_get_arg(1))) {
                $defaults = func_get_arg(1);
            }

            $component = new $componentClass($id, $defaults);

            foreach ($defaults as $key => $value) {
                if (str_starts_with($key, '__slot:')) {
                    $slotName = substr($key, 8);
                    if (method_exists($component, 'setSlot')) {
                        $component->setSlot($slotName, $value);
                    }
                }
            }

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

        return ucfirst($prefix);
    }

    /**
     * Vide le cache des composants
     */
    public static function clearCache(): void
    {
        self::initializeCollections();
        self::$componentCache->clear();
    }

    /**
     * Enregistre dynamiquement un namespace à partir d'une instance de composant
     */
    public static function registerNamespaceFromInstance(object $instance): void
    {
        if (!$instance instanceof Component) {
            return;
        }

        $class = get_class($instance);

        foreach (self::$namespaces as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return;
            }
        }

        $parts = explode('\\', $class);
        array_pop($parts);
        $namespace = implode('\\', $parts) . '\\';

        if (!in_array($namespace, self::$namespaces, true)) {
            self::$namespaces[] = $namespace;
            Config::load();
            $existing = Config::get('component_namespaces', []);
            if (!is_array($existing)) {
                $existing = [];
            }

            if (!in_array($namespace, $existing, true)) {
                $existing[] = $namespace;
                Config::set('component_namespaces', $existing);

                try {
                    Config::save();
                    error_log("[Impulse] Namespace enregistré automatiquement : $namespace");
                } catch (\Throwable $e) {
                    error_log("[Impulse] Erreur lors de la sauvegarde du namespace : " . $e->getMessage());
                }
            }
        }
    }
}
