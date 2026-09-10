<?php

declare(strict_types=1);

use Yume\Shared\Support\Env;

return [
    // Used to sign CSRF tokens. Rotating it invalidates outstanding tokens only,
    // not sessions.
    'app_secret' => Env::require('APP_SECRET'),

    'password' => [
        'minimum_length' => Env::int('PASSWORD_MIN_LENGTH', 12),
        'argon2' => [
            'memory_cost' => Env::int('ARGON2_MEMORY_COST', 65536),
            'time_cost' => Env::int('ARGON2_TIME_COST', 4),
            'threads' => Env::int('ARGON2_THREADS', 2),
        ],
        'max_failed_attempts' => Env::int('AUTH_MAX_FAILED_ATTEMPTS', 8),
        'lock_seconds' => Env::int('AUTH_LOCK_SECONDS', 900),
    ],

    'session' => [
        'absolute_lifetime' => Env::int('SESSION_LIFETIME_SECONDS', 2592000),
        'idle_extension' => Env::int('SESSION_IDLE_EXTENSION_SECONDS', 1209600),
        'rotate_after' => Env::int('SESSION_ROTATE_AFTER_SECONDS', 3600),
        'max_concurrent' => Env::int('SESSION_MAX_CONCURRENT', 10),
        // Off only for plain-HTTP local development.
        'secure_cookies' => Env::bool('SESSION_SECURE_COOKIES', true),
    ],

    // Caddy and nginx sit in front; only they may set X-Forwarded-For.
    'trusted_proxies' => Env::list('TRUSTED_PROXIES', ['172.16.0.0/12', '10.0.0.0/8', '127.0.0.1']),

    'cors' => [
        'allowed_origins' => Env::list('CORS_ALLOWED_ORIGINS', []),
    ],

    'hsts' => Env::bool('SECURITY_HSTS', true),

    'risk' => [
        'thresholds' => [
            'monitor' => Env::int('RISK_THRESHOLD_MONITOR', 20),
            'rate_limit' => Env::int('RISK_THRESHOLD_RATE_LIMIT', 40),
            'challenge' => Env::int('RISK_THRESHOLD_CHALLENGE', 60),
            'restrict' => Env::int('RISK_THRESHOLD_RESTRICT', 75),
            'block' => Env::int('RISK_THRESHOLD_BLOCK', 90),
        ],
        'allowed_user_agents' => Env::list('RISK_ALLOWED_USER_AGENTS', []),
    ],
];
