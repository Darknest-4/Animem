<?php

declare(strict_types=1);

use Yume\Api\Application\Auth\Command\ChangePasswordCommand;
use Yume\Api\Application\Auth\Command\LoginCommand;
use Yume\Api\Application\Auth\Command\LogoutCommand;
use Yume\Api\Application\Auth\Command\RegisterCommand;
use Yume\Api\Application\Auth\Command\RequestPasswordResetCommand;
use Yume\Api\Application\Auth\Command\ResendVerificationCommand;
use Yume\Api\Application\Auth\Command\ResetPasswordCommand;
use Yume\Api\Application\Auth\Command\RevokeSessionCommand;
use Yume\Api\Application\Auth\Command\VerifyEmailCommand;
use Yume\Api\Application\Auth\Handler\ChangePasswordHandler;
use Yume\Api\Application\Auth\Handler\GetCurrentUserHandler;
use Yume\Api\Application\Auth\Handler\GetSessionsHandler;
use Yume\Api\Application\Auth\Handler\LoginHandler;
use Yume\Api\Application\Auth\Handler\LogoutHandler;
use Yume\Api\Application\Auth\Handler\RegisterHandler;
use Yume\Api\Application\Auth\Handler\RequestPasswordResetHandler;
use Yume\Api\Application\Auth\Handler\ResendVerificationHandler;
use Yume\Api\Application\Auth\Handler\ResetPasswordHandler;
use Yume\Api\Application\Auth\Handler\RevokeSessionHandler;
use Yume\Api\Application\Auth\Handler\VerifyEmailHandler;
use Yume\Api\Application\Auth\Query\GetCurrentUserQuery;
use Yume\Api\Application\Auth\Query\GetSessionsQuery;
use Yume\Api\Application\Feature\Handler\ListFeatureFlagsHandler;
use Yume\Api\Application\Feature\Query\ListFeatureFlagsQuery;
use Yume\Api\Application\Auth\Service\TokenIssuer;
use Yume\Api\Domain\Auth\Repository\CredentialRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\OneTimeTokenRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\Service\PasswordPolicy;
use Yume\Api\Domain\Auth\Service\SessionPolicy;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\Security\RateLimit\RateLimiterInterface;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Presentation\Http\Kernel;
use Yume\Api\Presentation\Http\Middleware\AuthenticateMiddleware;
use Yume\Api\Presentation\Http\Middleware\AuthorizeMiddleware;
use Yume\Api\Presentation\Http\Middleware\ClientIdentityMiddleware;
use Yume\Api\Presentation\Http\Middleware\CorsMiddleware;
use Yume\Api\Presentation\Http\Middleware\CsrfMiddleware;
use Yume\Api\Presentation\Http\Middleware\ErrorHandlerMiddleware;
use Yume\Api\Presentation\Http\Middleware\RateLimitMiddleware;
use Yume\Api\Presentation\Http\Middleware\RouteResolverMiddleware;
use Yume\Api\Presentation\Http\Middleware\SecurityGateMiddleware;
use Yume\Api\Presentation\Http\Middleware\SecurityHeadersMiddleware;
use Yume\Api\Presentation\Http\Router;
use Yume\Contracts\Bus\CommandBusInterface;
use Yume\Contracts\Bus\QueryBusInterface;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;
use Yume\Contracts\Logging\LoggerInterface;
use Yume\Contracts\Mail\MailerInterface;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Shared\Bus\CommandBus;
use Yume\Shared\Bus\QueryBus;
use Yume\Shared\Config\Config;
use Yume\Shared\Container\Container;
use Yume\Security\Password\PasswordHasher;
use Yume\Security\Token\TokenGenerator;

/**
 * Use-case wiring: the command/query bus maps and the HTTP middleware order.
 *
 * The middleware order below is security-critical and is asserted by
 * tests/Security/MiddlewareOrderTest.php.
 */
return static function (Container $container, Config $config): void {
    // ---------------------------------------------------- handlers with options
    $container->singleton(RegisterHandler::class, static fn (Container $c): RegisterHandler => new RegisterHandler(
        $c->get(UserRepositoryInterface::class),
        $c->get(CredentialRepositoryInterface::class),
        $c->get(RoleRepositoryInterface::class),
        $c->get(PasswordPolicy::class),
        $c->get(PasswordHasher::class),
        $c->get(RateLimiterInterface::class),
        $c->get(\Yume\Api\Application\Security\SecurityAuditor::class),
        $c->get(ConnectionInterface::class),
        $c->get(IdGeneratorInterface::class),
        $c->get(ClockInterface::class),
        $config->bool('security.require_email_verification', true),
    ));

    $container->singleton(LoginHandler::class, static fn (Container $c): LoginHandler => new LoginHandler(
        $c->get(UserRepositoryInterface::class),
        $c->get(CredentialRepositoryInterface::class),
        $c->get(SessionRepositoryInterface::class),
        $c->get(RoleRepositoryInterface::class),
        $c->get(PasswordHasher::class),
        $c->get(SessionPolicy::class),
        $c->get(RateLimiterInterface::class),
        $c->get(\Yume\Api\Application\Security\SecurityAuditor::class),
        $c->get(TokenGenerator::class),
        $c->get(IdGeneratorInterface::class),
        $c->get(ClockInterface::class),
        $config->int('security.password.max_failed_attempts', 8),
        $config->int('security.password.lock_seconds', 900),
    ));

    $container->singleton(TokenIssuer::class, static fn (Container $c): TokenIssuer => new TokenIssuer(
        $c->get(OneTimeTokenRepositoryInterface::class),
        $c->get(TokenGenerator::class),
        $c->get(MailerInterface::class),
        $c->get(IdGeneratorInterface::class),
        $c->get(ClockInterface::class),
        $c->get(LoggerInterface::class),
        $config->string('app.url', 'http://localhost:8080'),
    ));

    // ---------------------------------------------------------------- bus maps
    $container->singleton(CommandBusInterface::class, static fn (Container $c): CommandBusInterface => new CommandBus($c, [
        RegisterCommand::class => RegisterHandler::class,
        LoginCommand::class => LoginHandler::class,
        LogoutCommand::class => LogoutHandler::class,
        RevokeSessionCommand::class => RevokeSessionHandler::class,
        VerifyEmailCommand::class => VerifyEmailHandler::class,
        ResendVerificationCommand::class => ResendVerificationHandler::class,
        RequestPasswordResetCommand::class => RequestPasswordResetHandler::class,
        ResetPasswordCommand::class => ResetPasswordHandler::class,
        ChangePasswordCommand::class => ChangePasswordHandler::class,
    ]));

    $container->singleton(QueryBusInterface::class, static fn (Container $c): QueryBusInterface => new QueryBus($c, [
        GetCurrentUserQuery::class => GetCurrentUserHandler::class,
        GetSessionsQuery::class => GetSessionsHandler::class,
        ListFeatureFlagsQuery::class => ListFeatureFlagsHandler::class,
    ]));

    // ------------------------------------------------------------------ router
    $container->singleton(Router::class, static function (): Router {
        $router = new Router();
        (require __DIR__ . '/routes.php')($router);
        $router->compile();

        return $router;
    });

    // ------------------------------------------------------------------ kernel
    $container->singleton(Kernel::class, static fn (Container $c): Kernel => new Kernel(
        $c,
        $c->get(Router::class),
        [
            // 1. Catch everything, so no later failure can leak a stack trace.
            ErrorHandlerMiddleware::class,
            // 2. Cross-origin preflight, before any work is done.
            CorsMiddleware::class,
            // 3. Response hardening headers, applied on the way back out.
            SecurityHeadersMiddleware::class,
            // 4. Establish the trusted client IP and request id. Everything below
            //    depends on this being correct.
            ClientIdentityMiddleware::class,
            // 5. Match the route (and its feature flag) so later middleware can
            //    read the declared permissions and rate-limit policy.
            RouteResolverMiddleware::class,
            // 6. Bans and risk scoring, before any expensive work — a blocked
            //    client must not get to spend Argon2id cycles on a login attempt.
            SecurityGateMiddleware::class,
            // 7. Throttle, using the budget the risk decision may have tightened.
            RateLimitMiddleware::class,
            // 8. Identify the caller.
            AuthenticateMiddleware::class,
            // 9. CSRF — after authentication, because it binds to the session id.
            CsrfMiddleware::class,
            // 10. Enforce the route's declared permissions. Last gate before the
            //     controller; nothing downstream may re-open access.
            AuthorizeMiddleware::class,
        ],
    ));
};
