<?php

declare(strict_types=1);

use Yume\Shared\Support\Env;

return [
    'logging' => [
        // Containers log to stderr so the platform, not the app, owns log shipping.
        'stream' => Env::get('LOG_STREAM', 'php://stderr'),
        'level' => Env::get('LOG_LEVEL', 'info'),
        'channel' => Env::get('LOG_CHANNEL', 'api'),
    ],
    'mail' => [
        'driver' => Env::get('MAIL_DRIVER', 'log'),
        'host' => Env::get('MAIL_HOST', 'mailpit'),
        'port' => Env::int('MAIL_PORT', 1025),
        'from' => Env::get('MAIL_FROM', 'noreply@yume.local'),
    ],
    'features' => [
        'allow_env_overrides' => Env::bool('FEATURE_ENV_OVERRIDES', true),
    ],
    'jikan' => [
        'base_url' => Env::get('JIKAN_BASE_URL', 'https://api.jikan.moe/v4'),
        'timeout_seconds' => Env::int('JIKAN_TIMEOUT', 5),
    ],
];
