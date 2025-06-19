<?php

namespace Impulse;

class Config
{
    private static array $data = [];
    private static ?string $path = null;
    private static bool $loaded = false;

    /**
     * Charge la configuration depuis le fichier fourni ou depuis le chemin détecté.
     */
    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        self::$path = $path ?? self::discoverPath();

        if (self::$path && file_exists(self::$path)) {
            try {
                self::$data = json_decode(
                    file_get_contents(self::$path),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\Throwable) {
                self::$data = [];
            }
        }

        self::$loaded = true;
    }

    private static function discoverPath(): string
    {
        $candidates = [
            getcwd() . '/.impulse/config.json',
            getcwd() . '/../.impulse/config.json',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    /**
     * Récupère une valeur de configuration ou la valeur par défaut.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$data[$key] ?? $default;
    }

    /**
     * Définit une valeur de configuration en mémoire.
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::$loaded) {
            self::load();
        }

        self::$data[$key] = $value;
    }

    /**
     * Sauvegarde la configuration dans le fichier.
     * @throws \JsonException
     */
    public static function save(?string $path = null): void
    {
        if (!self::$loaded) {
            self::load($path);
        }

        $path = $path ?? self::$path ?? self::discoverPath();
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        file_put_contents(
            $path,
            json_encode(self::$data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
