<?php

use Impulse\Core\Handler\AjaxHandler;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    (new AjaxHandler())->handle();
} catch (JsonException $e) {
    throw new \RuntimeException($e->getMessage());
}
