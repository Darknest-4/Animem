<?php

declare(strict_types=1);

/**
 * The only PHP file below the web root.
 *
 * Everything else — source, config, migrations, storage — lives outside
 * `public/` and is unreachable over HTTP. The legacy deployment served its
 * entire source tree, including config JSON with the production database
 * password and a directory of htpasswd files.
 */

use Yume\Api\Presentation\Http\Kernel;
use Yume\Api\Presentation\Http\Request\Request;
use Yume\Api\Presentation\Http\Response\JsonResponse;

$container = require dirname(__DIR__) . '/bootstrap/app.php';

$request = Request::fromGlobals(
    $_SERVER,
    $_GET,
    array_map('strval', $_COOKIE),
    file_get_contents('php://input') ?: '',
);

$response = $container->get(Kernel::class)->handle($request);

http_response_code($response->status());

foreach ($response->headers() as $name => $value) {
    header($name . ': ' . $value, true);
}

if ($response instanceof JsonResponse) {
    foreach ($response->cookies() as $cookie) {
        // header() with replace=false so multiple Set-Cookie lines survive.
        header('Set-Cookie: ' . $cookie, false);
    }
}

echo $response->bodyAsString();
