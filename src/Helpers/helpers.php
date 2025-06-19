<?php

namespace Impulse\Core;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return rtrim(__DIR__ . '/../../', '/') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

use Composer\Autoload\ClassLoader;

if (!function_exists('namespace_to_path')) {
    function namespace_to_path(string $namespace): ?string
    {
        static $loader = null;

        if (!$loader) {
            $autoloadFile = base_path('vendor/autoload.php');
            if (!file_exists($autoloadFile)) {
                return null;
            }

            $loader = require $autoloadFile;
        }

        /** @var ClassLoader $loader */
        $prefixesPsr4 = $loader->getPrefixesPsr4();

        foreach ($prefixesPsr4 as $prefix => $dirs) {
            if (str_starts_with($namespace, $prefix)) {
                $relative = str_replace('\\', '/', substr($namespace, strlen($prefix)));
                return rtrim($dirs[0], '/') . '/' . $relative;
            }
        }

        return null;
    }
}
