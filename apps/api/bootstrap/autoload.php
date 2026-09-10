<?php

declare(strict_types=1);

/**
 * Autoloading.
 *
 * Composer's autoloader is used when present. The PSR-4 fallback below exists
 * because this application has zero runtime dependencies by design, so it must
 * be able to boot — in a container, in CI, in a rescue shell — without a
 * `composer install` having succeeded first. Dev tooling (PHPUnit, PHPStan)
 * still comes from Composer.
 */

$composer = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($composer)) {
    require_once $composer;

    return;
}

$prefixes = [
    'Yume\\Api\\' => dirname(__DIR__) . '/src/',
    'Yume\\Contracts\\' => dirname(__DIR__, 3) . '/packages/Contracts/src/',
    'Yume\\Shared\\' => dirname(__DIR__, 3) . '/packages/Shared/src/',
    'Yume\\Database\\' => dirname(__DIR__, 3) . '/packages/Database/src/',
    'Yume\\Security\\' => dirname(__DIR__, 3) . '/packages/Security/src/',
];

spl_autoload_register(static function (string $class) use ($prefixes): void {
    foreach ($prefixes as $prefix => $baseDirectory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDirectory . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require_once $file;

            return;
        }
    }
});
