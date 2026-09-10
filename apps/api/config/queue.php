<?php

declare(strict_types=1);

use Yume\Shared\Support\Env;

return [
    'name' => Env::get('QUEUE_NAME', 'default'),
    'max_attempts' => Env::int('QUEUE_MAX_ATTEMPTS', 5),
    'poll_interval_seconds' => Env::int('QUEUE_POLL_INTERVAL', 2),
];
