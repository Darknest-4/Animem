<?php

declare(strict_types=1);

use Yume\Shared\Support\Env;

return [
    'dsn' => sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;options=--client_encoding=UTF8',
        Env::get('DB_HOST', 'postgres'),
        Env::int('DB_PORT', 5432),
        Env::get('DB_DATABASE', 'yume'),
    ),
    'username' => Env::get('DB_USERNAME', 'yume'),
    // Required, with no fallback: a silent default password is how the legacy
    // project ended up with the same credentials in four committed files.
    'password' => Env::require('DB_PASSWORD'),
    'connect_timeout' => Env::int('DB_CONNECT_TIMEOUT', 5),
    'migrations_path' => __DIR__ . '/../src/Infrastructure/Persistence/Migration',
];
