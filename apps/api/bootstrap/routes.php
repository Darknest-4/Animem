<?php

declare(strict_types=1);

use Yume\Api\Presentation\Http\Controller\AdminUserController;
use Yume\Api\Presentation\Http\Controller\AuthController;
use Yume\Api\Presentation\Http\Controller\FeatureController;
use Yume\Api\Presentation\Http\Controller\HealthController;
use Yume\Api\Presentation\Http\Controller\SessionController;
use Yume\Api\Presentation\Http\Router;

/**
 * The route table.
 *
 * Every entry states its access rule: ->public(), ->authenticated() or
 * ->can(...). Router::compile() refuses to boot if any route omits it, so a
 * forgotten permission check is a startup crash rather than an open endpoint.
 *
 * This file is the authoritative answer to "who can reach what". Reading it
 * top to bottom should be enough to audit the API's access control.
 */
return static function (Router $router): void {
    $v1 = '/api/v1';

    // ---------------------------------------------------------------- platform
    $router->get('/health', [HealthController::class, 'live'], 'health.live')
        ->public();

    $router->get('/health/ready', [HealthController::class, 'ready'], 'health.ready')
        ->public();

    // -------------------------------------------------------------------- auth
    $router->post($v1 . '/auth/register', [AuthController::class, 'register'], 'auth.register')
        ->public()
        ->rateLimit('auth.register')
        // No session exists yet, so there is nothing to bind a CSRF token to.
        ->withoutCsrf();

    $router->post($v1 . '/auth/login', [AuthController::class, 'login'], 'auth.login')
        ->public()
        ->rateLimit('auth.login')
        ->withoutCsrf();

    $router->post($v1 . '/auth/logout', [AuthController::class, 'logout'], 'auth.logout')
        ->authenticated();

    $router->get($v1 . '/auth/me', [AuthController::class, 'me'], 'auth.me')
        ->authenticated();

    // ---------------------------------------------------------------- sessions
    $router->get($v1 . '/auth/sessions', [SessionController::class, 'index'], 'sessions.index')
        ->authenticated();

    $router->delete(
        $v1 . '/auth/sessions/{id:[0-9a-fA-F-]{36}}',
        [SessionController::class, 'revoke'],
        'sessions.revoke',
    )->authenticated();

    $router->delete($v1 . '/auth/sessions', [SessionController::class, 'revokeAll'], 'sessions.revoke_all')
        ->authenticated();

    // ---------------------------------------------------------------- features
    // Public: the front end must know which features to render before sign-in.
    // Callers holding feature_flag.view additionally get the rollout definition.
    $router->get($v1 . '/features', [FeatureController::class, 'index'], 'features.index')
        ->public();

    // ------------------------------------------------------------------- admin
    $router->get($v1 . '/admin/users', [AdminUserController::class, 'index'], 'admin.users.index')
        ->can('admin.access', 'user.view');
};
