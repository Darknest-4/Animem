<?php

declare(strict_types=1);

use Yume\Shared\Config\Config;
use Yume\Shared\Container\Container;
use Yume\Shared\Support\Env;

/**
 * Composition root.
 *
 * Returns a fully wired container. Used identically by the HTTP entry point
 * (public/index.php), the CLI (bin/console), the worker and the test suite —
 * one boot path means the tests exercise the real wiring.
 */

require_once __DIR__ . '/autoload.php';

// A .env file is a developer convenience; in containers the environment is
// already populated and always wins.
Env::loadFile(dirname(__DIR__, 3) . '/.env');

$config = Config::fromDirectory(__DIR__ . '/../config');

date_default_timezone_set($config->string('app.timezone', 'UTC'));

// Errors are surfaced as exceptions and rendered by ErrorHandlerMiddleware.
// display_errors stays off unconditionally: the legacy site turned it on in six
// separate places and printed failing SQL to the browser.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

$container = new Container();
$container->instance(Config::class, $config);
$container->instance(Container::class, $container);

(require __DIR__ . '/container.php')($container, $config);
(require __DIR__ . '/providers.php')($container, $config);

return $container;
