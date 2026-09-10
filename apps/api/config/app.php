<?php

declare(strict_types=1);

use Yume\Shared\Support\Env;

return [
    'name' => Env::get('APP_NAME', 'Yume'),
    'env' => Env::get('APP_ENV', 'production'),
    // Never default to true: a missing APP_DEBUG must not expose stack traces.
    'debug' => Env::bool('APP_DEBUG', false),
    'version' => Env::get('APP_VERSION', 'dev'),
    'url' => Env::get('APP_URL', 'http://localhost:8080'),
    'timezone' => 'UTC',
];
