<?php

/**
 * Database credentials for the legacy site.
 *
 * The environment is the source of truth. The JSON files that used to hold these
 * values were tracked in git — the same production password appeared in four of
 * them — so they have been removed from the repository. A file is still read as
 * a fallback if one exists on the server, which is what makes this change safe
 * to deploy: nothing breaks the moment it lands, and the secret can be moved to
 * the environment at whatever pace suits.
 *
 * Set, in the web server's environment (Apache SetEnv, php-fpm env[], or the
 * shell that starts PHP):
 *
 *   ANIMEM_DB_HOST      default: localhost
 *   ANIMEM_DB_NAME
 *   ANIMEM_DB_USER
 *   ANIMEM_DB_PASSWORD
 *   ANIMEM_SITE_DOMAIN        default: animem.org
 *   ANIMEM_VIDEO_DOMAIN       default: animem.hu
 *
 * A few legacy pages open a second connection to the `xanimem` database. Its
 * credentials follow the same pattern with a DB2 prefix:
 *
 *   ANIMEM_DB2_HOST     ANIMEM_DB2_NAME     ANIMEM_DB2_USER     ANIMEM_DB2_PASSWORD
 *
 * This file deliberately contains no credentials and is safe to keep tracked.
 */

if (!function_exists('animem_env')) {
    /**
     * Reads an environment variable, tolerating the several ways PHP exposes them.
     */
    function animem_env(string $key, ?string $default = null): ?string
    {
        foreach ([getenv($key), $_ENV[$key] ?? false, $_SERVER[$key] ?? false] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return $default;
    }
}

if (!function_exists('animem_db_credentials')) {
    /**
     * @param string|null $fallbackJsonPath a legacy config file to read if the
     *                                      environment is not populated
     * @param string|null $fallbackJsonKey  top-level key inside that file
     *                                      (dbconfig.json nests under "default")
     * @return array{host: string, username: string, password: string, database: string}
     */
    function animem_db_credentials(?string $fallbackJsonPath = null, ?string $fallbackJsonKey = null): array
    {
        $fromFile = [];

        if ($fallbackJsonPath !== null && is_readable($fallbackJsonPath)) {
            $decoded = json_decode((string) file_get_contents($fallbackJsonPath), true);

            if (is_array($decoded)) {
                if ($fallbackJsonKey !== null && isset($decoded[$fallbackJsonKey]) && is_array($decoded[$fallbackJsonKey])) {
                    $decoded = $decoded[$fallbackJsonKey];
                }

                // config.json nests the connection under "db"; the others are flat.
                $fromFile = isset($decoded['db']) && is_array($decoded['db']) ? $decoded['db'] : $decoded;
            }
        }

        $credentials = [
            'host' => animem_env('ANIMEM_DB_HOST', $fromFile['host'] ?? 'localhost'),
            'username' => animem_env('ANIMEM_DB_USER', $fromFile['username'] ?? ($fromFile['user'] ?? null)),
            'password' => animem_env('ANIMEM_DB_PASSWORD', $fromFile['password'] ?? null),
            // dbconfig.json calls it dbname, the others call it database.
            'database' => animem_env('ANIMEM_DB_NAME', $fromFile['database'] ?? ($fromFile['dbname'] ?? null)),
        ];

        foreach (['username', 'password', 'database'] as $required) {
            if ($credentials[$required] === null || $credentials[$required] === '') {
                // Fail with a message that says how to fix it, and without ever
                // echoing what was found.
                throw new RuntimeException(
                    'Database credentials are not configured. Set ANIMEM_DB_USER, '
                    . 'ANIMEM_DB_PASSWORD and ANIMEM_DB_NAME in the environment '
                    . '(see Config/credentials.php).',
                );
            }
        }

        return $credentials;
    }
}

if (!function_exists('animem_site_config')) {
    /**
     * @return array{domain: string, videoDomain: string}
     */
    function animem_site_config(?string $fallbackJsonPath = null): array
    {
        $fromFile = [];

        if ($fallbackJsonPath !== null && is_readable($fallbackJsonPath)) {
            $decoded = json_decode((string) file_get_contents($fallbackJsonPath), true);

            if (is_array($decoded) && isset($decoded['site']) && is_array($decoded['site'])) {
                $fromFile = $decoded['site'];
            }
        }

        return [
            'domain' => (string) animem_env('ANIMEM_SITE_DOMAIN', $fromFile['domain'] ?? 'animem.org'),
            'videoDomain' => (string) animem_env('ANIMEM_VIDEO_DOMAIN', $fromFile['videoDomain'] ?? 'animem.hu'),
        ];
    }
}

if (!function_exists('animem_db2_credentials')) {
    /**
     * The secondary (`xanimem`) connection used by a handful of legacy pages.
     *
     * There is no file fallback: these credentials were hardcoded in the PHP
     * itself, so there is no legacy file to fall back to and the environment is
     * the only source.
     *
     * @return array{host: string, username: string, password: string, database: string}
     */
    function animem_db2_credentials(): array
    {
        $credentials = [
            'host' => (string) animem_env('ANIMEM_DB2_HOST', 'localhost'),
            'username' => animem_env('ANIMEM_DB2_USER'),
            'password' => animem_env('ANIMEM_DB2_PASSWORD'),
            'database' => animem_env('ANIMEM_DB2_NAME'),
        ];

        foreach (['username', 'password', 'database'] as $required) {
            if ($credentials[$required] === null || $credentials[$required] === '') {
                throw new RuntimeException(
                    'Secondary database credentials are not configured. Set '
                    . 'ANIMEM_DB2_USER, ANIMEM_DB2_PASSWORD and ANIMEM_DB2_NAME '
                    . '(see Config/credentials.php).',
                );
            }
        }

        return $credentials;
    }
}
