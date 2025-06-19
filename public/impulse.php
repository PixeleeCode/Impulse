<?php

use Impulse\Core\Handler\AjaxDispatcher;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    (new AjaxDispatcher())->handle();
} catch (JsonException $e) {
    throw new \RuntimeException($e->getMessage());
}
