<?php

require_once __DIR__ . '/credentials.php';

/**
 * Configuration for the legacy site.
 *
 * Values come from the environment; config.json is read only as a fallback if a
 * copy still exists on the server. It used to be tracked in git with the
 * production password in it — see Config/credentials.php.
 */
class DbConfig
{
    const DB_FILE = 'config.json';

    /** @var null|array */
    static $config = null;

    static function loadConfig()
    {
        if (null === self::$config) {
            $fallback = __DIR__ . '/' . self::DB_FILE;

            self::$config = [
                'db' => animem_db_credentials($fallback),
                'site' => animem_site_config($fallback),
            ];
        }
    }

    /**
     * @return array{host: string, username: string, password: string, database: string}
     */
    static function getDbConfig()
    {
        self::loadConfig();

        return self::$config['db'];
    }

    /**
     * @return array{domain: string, videoDomain: string}
     */
    static function getSiteConfig()
    {
        self::loadConfig();

        return self::$config['site'];
    }
}
