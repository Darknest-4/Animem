<?php

declare(strict_types=1);

/**
 * Test bootstrap.
 *
 * Loads the same autoloader the application uses, so the suite exercises the
 * real wiring rather than a test-only arrangement of it.
 */

require_once dirname(__DIR__) . '/bootstrap/autoload.php';

// PHPUnit's <env> entries land in $_ENV/$_SERVER but not always in getenv(),
// which is what Env reads. Mirror them across.
foreach ($_ENV as $key => $value) {
    if (is_string($value) && getenv($key) === false) {
        putenv($key . '=' . $value);
    }
}
