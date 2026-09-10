<?php

declare(strict_types=1);

use Yume\Shared\Support\Env;

return [
    'prefix' => Env::get('CACHE_PREFIX', 'yume:'),
    'default_ttl' => Env::int('CACHE_DEFAULT_TTL', 300),
];
