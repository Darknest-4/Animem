<?php

declare(strict_types=1);

use Yume\Api\Application\Feature\FeatureFlagResolver;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\CredentialRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\OneTimeTokenRepositoryInterface;
use Yume\Api\Domain\Auth\Repository\SessionRepositoryInterface;
use Yume\Api\Domain\Auth\Service\PasswordPolicy;
use Yume\Api\Domain\Auth\Service\SessionPolicy;
use Yume\Api\Domain\Authorization\Repository\RoleRepositoryInterface;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;
use Yume\Api\Domain\Episode\Repository\EpisodeRepositoryInterface;
use Yume\Api\Domain\Feature\Repository\FeatureFlagRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityAuditRepositoryInterface;
use Yume\Api\Domain\Security\Ban\BanRepositoryInterface;
use Yume\Api\Domain\Security\Detection\AutomationDetector;
use Yume\Api\Domain\Security\Detection\BotDetector;
use Yume\Api\Domain\Security\Detection\NetworkReputationRepositoryInterface;
use Yume\Api\Domain\Security\Detection\ProxyDetector;
use Yume\Api\Domain\Security\Detection\TorDetector;
use Yume\Api\Domain\Security\Detection\VpnDetector;
use Yume\Api\Domain\Security\RateLimit\RateLimiterInterface;
use Yume\Api\Domain\Security\Risk\RiskEngine;
use Yume\Api\Domain\User\Repository\UserRepositoryInterface;
use Yume\Api\Infrastructure\Cache\ApcuCache;
use Yume\Api\Infrastructure\Persistence\Repository\PdoAnimeRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoBanRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoEpisodeRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoCredentialRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoFeatureFlagRepository;
use Yume\Api\Infrastructure\Mail\LogMailer;
use Yume\Api\Infrastructure\Mail\NullMailer;
use Yume\Api\Infrastructure\Mail\SmtpMailer;
use Yume\Api\Infrastructure\Persistence\Repository\PdoNetworkReputationRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoOneTimeTokenRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoRoleRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoSecurityAuditRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoSessionRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoUploaderRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoUserRepository;
use Yume\Api\Infrastructure\Queue\PdoQueue;
use Yume\Api\Infrastructure\Security\PdoRateLimiter;
use Yume\Api\Infrastructure\Security\TrustedProxyResolver;
use Yume\Api\Presentation\Http\Controller\HealthController;
use Yume\Api\Presentation\Http\Middleware\AuthenticateMiddleware;
use Yume\Api\Presentation\Http\Middleware\CorsMiddleware;
use Yume\Api\Presentation\Http\Middleware\ErrorHandlerMiddleware;
use Yume\Api\Presentation\Http\Middleware\SecurityHeadersMiddleware;
use Yume\Contracts\Cache\CacheInterface;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Feature\FeatureFlagsInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;
use Yume\Contracts\Logging\LoggerInterface;
use Yume\Contracts\Mail\MailerInterface;
use Yume\Contracts\Persistence\ConnectionInterface;
use Yume\Contracts\Queue\QueueInterface;
use Yume\Database\Connection;
use Yume\Database\Migration\Migrator;
use Yume\Shared\Clock\SystemClock;
use Yume\Shared\Config\Config;
use Yume\Shared\Container\Container;
use Yume\Shared\Identity\UuidV7Generator;
use Yume\Shared\Logging\JsonLogger;
use Yume\Security\Csrf\CsrfTokenManager;
use Yume\Security\Password\PasswordHasher;
use Yume\Security\Token\TokenGenerator;

/**
 * Service bindings.
 *
 * Interfaces are bound to concrete classes here and nowhere else. Every arrow in
 * the dependency graph that crosses a layer boundary — Domain asking for a
 * repository, Application asking for a clock — is resolved at this one point,
 * which is what keeps the inner layers free of infrastructure imports.
 */
return static function (Container $container, Config $config): void {
    // ---------------------------------------------------------------- platform
    $container->singleton(ClockInterface::class, static fn (): ClockInterface => new SystemClock());

    $container->singleton(
        IdGeneratorInterface::class,
        static fn (Container $c): IdGeneratorInterface => new UuidV7Generator($c->get(ClockInterface::class)),
    );

    $container->singleton(
        LoggerInterface::class,
        static fn (Container $c): LoggerInterface => new JsonLogger(
            $c->get(ClockInterface::class),
            $config->string('services.logging.stream', 'php://stderr'),
            $config->string('services.logging.level', 'info'),
            [],
            $config->string('services.logging.channel', 'api'),
        ),
    );

    $container->singleton(
        CacheInterface::class,
        static fn (): CacheInterface => new ApcuCache($config->string('cache.prefix', 'yume:')),
    );

    // -------------------------------------------------------------- persistence
    $container->singleton(ConnectionInterface::class, static fn (): ConnectionInterface => new Connection(
        $config->string('database.dsn'),
        $config->string('database.username'),
        $config->string('database.password'),
        $config->int('database.connect_timeout', 5),
    ));

    $container->singleton(Migrator::class, static fn (Container $c): Migrator => new Migrator(
        $c->get(ConnectionInterface::class),
        $c->get(ClockInterface::class),
        $config->string('database.migrations_path'),
    ));

    $container->bind(UserRepositoryInterface::class, PdoUserRepository::class);
    $container->bind(CredentialRepositoryInterface::class, PdoCredentialRepository::class);
    $container->bind(SessionRepositoryInterface::class, PdoSessionRepository::class);
    $container->bind(RoleRepositoryInterface::class, PdoRoleRepository::class);
    $container->bind(FeatureFlagRepositoryInterface::class, PdoFeatureFlagRepository::class);
    $container->bind(BanRepositoryInterface::class, PdoBanRepository::class);
    $container->bind(SecurityAuditRepositoryInterface::class, PdoSecurityAuditRepository::class);
    $container->bind(NetworkReputationRepositoryInterface::class, PdoNetworkReputationRepository::class);
    $container->bind(RateLimiterInterface::class, PdoRateLimiter::class);
    $container->bind(OneTimeTokenRepositoryInterface::class, PdoOneTimeTokenRepository::class);

    // Catalogue
    $container->bind(AnimeRepositoryInterface::class, PdoAnimeRepository::class);
    $container->bind(EpisodeRepositoryInterface::class, PdoEpisodeRepository::class);
    $container->bind(UploaderRepositoryInterface::class, PdoUploaderRepository::class);

    $container->singleton(QueueInterface::class, static fn (Container $c): QueueInterface => new PdoQueue(
        $c->get(ConnectionInterface::class),
        $c->get(IdGeneratorInterface::class),
        $c->get(ClockInterface::class),
        $config->string('queue.name', 'default'),
        $config->int('queue.max_attempts', 5),
    ));

    // --------------------------------------------------------------------- mail
    // 'log' is the default: registration must not fail because SMTP is not
    // configured yet, and a developer needs to see the verification link.
    $container->singleton(MailerInterface::class, static fn (Container $c): MailerInterface => match ($config->string('services.mail.driver', 'log')) {
        'smtp' => new SmtpMailer(
            $c->get(LoggerInterface::class),
            $config->string('services.mail.host', 'mailpit'),
            $config->int('services.mail.port', 1025),
            $config->string('services.mail.from', 'noreply@yume.local'),
            $config->string('services.mail.username') ?: null,
            $config->string('services.mail.password') ?: null,
        ),
        'null' => new NullMailer(),
        default => new LogMailer(
            $c->get(LoggerInterface::class),
            $config->string('services.mail.from', 'noreply@yume.local'),
        ),
    });

    // ----------------------------------------------------------------- security
    $container->singleton(PasswordHasher::class, static fn (): PasswordHasher => new PasswordHasher(
        PASSWORD_ARGON2ID,
        [
            'memory_cost' => $config->int('security.password.argon2.memory_cost', 65536),
            'time_cost' => $config->int('security.password.argon2.time_cost', 4),
            'threads' => $config->int('security.password.argon2.threads', 2),
        ],
    ));

    $container->singleton(PasswordPolicy::class, static fn (): PasswordPolicy => new PasswordPolicy(
        $config->int('security.password.minimum_length', 12),
    ));

    $container->singleton(SessionPolicy::class, static fn (): SessionPolicy => new SessionPolicy(
        $config->int('security.session.absolute_lifetime', 2592000),
        $config->int('security.session.idle_extension', 1209600),
        $config->int('security.session.rotate_after', 3600),
        $config->int('security.session.max_concurrent', 10),
    ));

    $container->singleton(TokenGenerator::class, static fn (): TokenGenerator => new TokenGenerator(32));

    $container->singleton(CsrfTokenManager::class, static fn (): CsrfTokenManager => new CsrfTokenManager(
        $config->string('security.app_secret'),
    ));

    $container->singleton(TrustedProxyResolver::class, static fn (): TrustedProxyResolver => new TrustedProxyResolver(
        array_map('strval', $config->array('security.trusted_proxies', [])),
    ));

    $container->singleton(RiskEngine::class, static fn (Container $c): RiskEngine => new RiskEngine(
        [
            new BotDetector(array_map('strval', $config->array('security.risk.allowed_user_agents', []))),
            new AutomationDetector(),
            new ProxyDetector(),
            new TorDetector($c->get(NetworkReputationRepositoryInterface::class)),
            new VpnDetector($c->get(NetworkReputationRepositoryInterface::class)),
        ],
        $c->get(BanRepositoryInterface::class),
        array_map('intval', $config->array('security.risk.thresholds', [])),
    ));

    // ----------------------------------------------------------------- features
    $container->singleton(
        FeatureFlagsInterface::class,
        static fn (Container $c): FeatureFlagsInterface => $c->get(FeatureFlagResolver::class),
    );

    $container->singleton(FeatureFlagResolver::class, static fn (Container $c): FeatureFlagResolver => new FeatureFlagResolver(
        $c->get(FeatureFlagRepositoryInterface::class),
        $c->get(ClockInterface::class),
        $c->get(LoggerInterface::class),
        $config->bool('services.features.allow_env_overrides', true),
    ));

    // --------------------------------------------------------------- middleware
    $container->singleton(ErrorHandlerMiddleware::class, static fn (Container $c): ErrorHandlerMiddleware => new ErrorHandlerMiddleware(
        $c->get(LoggerInterface::class),
        $config->bool('app.debug', false),
    ));

    $container->singleton(SecurityHeadersMiddleware::class, static fn (): SecurityHeadersMiddleware => new SecurityHeadersMiddleware(
        $config->bool('security.hsts', true),
    ));

    $container->singleton(CorsMiddleware::class, static fn (): CorsMiddleware => new CorsMiddleware(
        array_map('strval', $config->array('security.cors.allowed_origins', [])),
    ));

    $container->singleton(AuthenticateMiddleware::class, static fn (Container $c): AuthenticateMiddleware => new AuthenticateMiddleware(
        $c->get(SessionRepositoryInterface::class),
        $c->get(SessionPolicy::class),
        $c->get(TokenGenerator::class),
        $c->get(\Yume\Api\Application\Security\SecurityAuditor::class),
        $c->get(ClockInterface::class),
        $config->bool('security.session.secure_cookies', true),
    ));

    // -------------------------------------------------------------- controllers
    $container->singleton(HealthController::class, static fn (Container $c): HealthController => new HealthController(
        $c->get(ConnectionInterface::class),
        $c->get(ClockInterface::class),
        $config->string('app.version', 'dev'),
    ));
};
