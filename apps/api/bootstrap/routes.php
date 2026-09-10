<?php

declare(strict_types=1);

use Yume\Api\Presentation\Http\Controller\AdminFeatureController;
use Yume\Api\Presentation\Http\Controller\AdminRoleController;
use Yume\Api\Presentation\Http\Controller\AdminSecurityController;
use Yume\Api\Presentation\Http\Controller\AdminUserController;
use Yume\Api\Presentation\Http\Controller\AnimeController;
use Yume\Api\Presentation\Http\Controller\EpisodeController;
use Yume\Api\Presentation\Http\Controller\StatsController;
use Yume\Api\Presentation\Http\Controller\UploaderController;
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

    // --------------------------------------------------- email and password
    // All four are pre-session: the caller holds a mailed token, not a cookie,
    // so there is nothing to bind a CSRF token to.
    $router->post($v1 . '/auth/email/verify', [AuthController::class, 'verifyEmail'], 'auth.email.verify')
        ->public()
        ->rateLimit('auth.token_redeem')
        ->withoutCsrf();

    // The handler applies the stricter, subnet-keyed auth.email_dispatch budget
    // on top; this one only stops hammering from a single address.
    $router->post($v1 . '/auth/email/resend', [AuthController::class, 'resendVerification'], 'auth.email.resend')
        ->public()
        ->rateLimit('auth.token_redeem')
        ->withoutCsrf();

    $router->post($v1 . '/auth/password/forgot', [AuthController::class, 'forgotPassword'], 'auth.password.forgot')
        ->public()
        ->rateLimit('auth.token_redeem')
        ->withoutCsrf();

    $router->post($v1 . '/auth/password/reset', [AuthController::class, 'resetPassword'], 'auth.password.reset')
        ->public()
        ->rateLimit('auth.token_redeem')
        ->withoutCsrf();

    // Signed in, and still requires the current password.
    $router->post($v1 . '/auth/password/change', [AuthController::class, 'changePassword'], 'auth.password.change')
        ->authenticated()
        ->rateLimit('auth.password_change');

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

    // --------------------------------------------------------------- catalogue
    // Reads are public: the whole point of an anime index is being readable, and
    // the `guest` role holds anime.view / episode.view / uploader.view. Drafts
    // are filtered out for anyone without anime.edit, inside the handler.
    $router->get($v1 . '/anime', [AnimeController::class, 'index'], 'anime.index')
        ->can('anime.view');

    $router->get($v1 . '/anime/{identifier:[A-Za-z0-9-]{1,200}}', [AnimeController::class, 'show'], 'anime.show')
        ->can('anime.view');

    $router->post($v1 . '/anime', [AnimeController::class, 'create'], 'anime.create')
        ->can('anime.create');

    $router->patch($v1 . '/anime/{id:[0-9a-fA-F-]{36}}', [AnimeController::class, 'update'], 'anime.update')
        ->can('anime.edit');

    // Publishing is separate from editing: filling in a draft and making it
    // public are different decisions, and the second one is auditable.
    $router->post($v1 . '/anime/{id:[0-9a-fA-F-]{36}}/publish', [AnimeController::class, 'publish'], 'anime.publish')
        ->can('anime.edit');

    $router->delete($v1 . '/anime/{id:[0-9a-fA-F-]{36}}', [AnimeController::class, 'delete'], 'anime.delete')
        ->can('anime.delete');

    // ---------------------------------------------------------------- episodes
    $router->get(
        $v1 . '/anime/{id:[0-9a-fA-F-]{36}}/episodes',
        [EpisodeController::class, 'index'],
        'episode.index',
    )->can('episode.view');

    $router->post(
        $v1 . '/anime/{id:[0-9a-fA-F-]{36}}/episodes',
        [EpisodeController::class, 'create'],
        'episode.create',
    )->can('episode.create');

    $router->post(
        $v1 . '/episodes/{id:[0-9a-fA-F-]{36}}/releases',
        [EpisodeController::class, 'addRelease'],
        'episode.release.add',
    )->can('episode.edit');

    $router->post(
        $v1 . '/episodes/{id:[0-9a-fA-F-]{36}}/publish',
        [EpisodeController::class, 'publish'],
        'episode.publish',
    )->can('episode.edit');

    $router->delete(
        $v1 . '/episodes/{id:[0-9a-fA-F-]{36}}',
        [EpisodeController::class, 'delete'],
        'episode.delete',
    )->can('episode.delete');

    // Lets the upload form validate a URL before submitting it.
    $router->get($v1 . '/episodes/allowed-hosts', [EpisodeController::class, 'allowedHosts'], 'episode.allowed_hosts')
        ->can('episode.view');

    // --------------------------------------------------------------- uploaders
    $router->get($v1 . '/uploaders', [UploaderController::class, 'index'], 'uploader.index')
        ->can('uploader.view');

    $router->get(
        $v1 . '/uploaders/{identifier:[A-Za-z0-9-]{1,200}}',
        [UploaderController::class, 'show'],
        'uploader.show',
    )->can('uploader.view');

    $router->post($v1 . '/uploaders', [UploaderController::class, 'create'], 'uploader.create')
        ->can('uploader.manage');

    $router->patch($v1 . '/uploaders/{id:[0-9a-fA-F-]{36}}', [UploaderController::class, 'update'], 'uploader.update')
        ->can('uploader.manage');

    // ------------------------------------------------------------------- stats
    $router->get($v1 . '/stats', [StatsController::class, 'overview'], 'stats.overview')
        ->can('stats.view');

    // ------------------------------------------------------------------- admin
    $router->get($v1 . '/admin/users', [AdminUserController::class, 'index'], 'admin.users.index')
        ->can('admin.access', 'user.view');

    // Suspending an account is a heavier act than reading the user list.
    $router->patch(
        $v1 . '/admin/users/{id:[0-9a-fA-F-]{36}}/status',
        [AdminUserController::class, 'setStatus'],
        'admin.users.status',
    )->can('admin.access', 'user.manage');

    // Roles. Reading the catalogue is separate from changing who holds what.
    $router->get($v1 . '/admin/roles', [AdminRoleController::class, 'index'], 'admin.roles.index')
        ->can('admin.access', 'role.view');

    $router->post(
        $v1 . '/admin/users/{id:[0-9a-fA-F-]{36}}/roles',
        [AdminRoleController::class, 'grant'],
        'admin.roles.grant',
    )->can('admin.access', 'role.manage');

    $router->delete(
        $v1 . '/admin/users/{id:[0-9a-fA-F-]{36}}/roles/{role:[a-z][a-z0-9_]*}',
        [AdminRoleController::class, 'revoke'],
        'admin.roles.revoke',
    )->can('admin.access', 'role.manage');

    // Feature flags: the whole point is changing these without a deploy.
    $router->get($v1 . '/admin/features', [AdminFeatureController::class, 'index'], 'admin.features.index')
        ->can('admin.access', 'feature_flag.view');

    $router->get(
        $v1 . '/admin/features/{key:[a-z][a-z0-9_]*}/history',
        [AdminFeatureController::class, 'history'],
        'admin.features.history',
    )->can('admin.access', 'feature_flag.view');

    $router->patch(
        $v1 . '/admin/features/{key:[a-z][a-z0-9_]*}',
        [AdminFeatureController::class, 'update'],
        'admin.features.update',
    )->can('admin.access', 'feature_flag.manage');

    // Security: reading the audit trail is a moderator-grade permission,
    // applying a ban is not.
    $router->get($v1 . '/admin/security/events', [AdminSecurityController::class, 'events'], 'admin.security.events')
        ->can('admin.access', 'security.view');

    $router->get(
        $v1 . '/admin/security/users/{id:[0-9a-fA-F-]{36}}/events',
        [AdminSecurityController::class, 'userEvents'],
        'admin.security.user_events',
    )->can('admin.access', 'security.view');

    $router->get($v1 . '/admin/security/bans', [AdminSecurityController::class, 'listBans'], 'admin.security.bans.index')
        ->can('admin.access', 'security.view');

    $router->post($v1 . '/admin/security/bans', [AdminSecurityController::class, 'createBan'], 'admin.security.bans.create')
        ->can('admin.access', 'security.manage');

    $router->delete(
        $v1 . '/admin/security/bans/{id:[0-9a-fA-F-]{36}}',
        [AdminSecurityController::class, 'liftBan'],
        'admin.security.bans.lift',
    )->can('admin.access', 'security.manage');
};
