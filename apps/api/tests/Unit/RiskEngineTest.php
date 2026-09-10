<?php

declare(strict_types=1);

namespace Yume\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yume\Api\Domain\Security\Ban\Ban;
use Yume\Api\Domain\Security\Ban\BanRepositoryInterface;
use Yume\Api\Domain\Security\Ban\BanScope;
use Yume\Api\Domain\Security\Ban\BanType;
use Yume\Api\Domain\Security\Detection\AutomationDetector;
use Yume\Api\Domain\Security\Detection\BotDetector;
use Yume\Api\Domain\Security\Detection\ProxyDetector;
use Yume\Api\Domain\Security\Risk\RiskAction;
use Yume\Api\Domain\Security\Risk\RiskContext;
use Yume\Api\Domain\Security\Risk\RiskEngine;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;

final class RiskEngineTest extends TestCase
{
    private const BROWSER = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/140.0 Safari/537.36';

    public function testAnOrdinaryBrowserRequestIsAllowedSilently(): void
    {
        $decision = $this->engine()->evaluate($this->context(), new \DateTimeImmutable());

        self::assertSame(RiskAction::Allow, $decision->action);
        self::assertSame(0, $decision->score->value);
        self::assertFalse($decision->shouldAudit());
    }

    public function testAnActiveBanShortCircuitsToBlock(): void
    {
        $engine = $this->engine(ban: new Ban(
            'ban-1',
            BanScope::Ip,
            BanType::Manual,
            '203.0.113.10',
            'spam',
            new \DateTimeImmutable('-1 day'),
            null,
        ));

        $decision = $engine->evaluate($this->context(), new \DateTimeImmutable());

        self::assertSame(RiskAction::Block, $decision->action);
        self::assertSame(100, $decision->score->value);
    }

    /**
     * The same client is scored differently depending on what it is reaching for:
     * a scripted client reading a public page is noise, the same client posting to
     * /auth/login is a credential-stuffing attempt.
     */
    public function testAScriptedClientIsScoredHigherOnCredentialEndpoints(): void
    {
        $engine = $this->engine();
        $now = new \DateTimeImmutable();

        $onPublicPage = $engine->evaluate(
            $this->context(userAgent: 'curl/8.5.0', path: '/api/v1/features'),
            $now,
        );
        $onLogin = $engine->evaluate(
            $this->context(userAgent: 'curl/8.5.0', path: '/api/v1/auth/login'),
            $now,
        );

        self::assertGreaterThan($onPublicPage->score->value, $onLogin->score->value);
        self::assertSame(RiskAction::Monitor, $onPublicPage->action);
        self::assertSame(RiskAction::RateLimit, $onLogin->action);
    }

    public function testMissingUserAgentIsASignal(): void
    {
        $decision = $this->engine()->evaluate($this->context(userAgent: ''), new \DateTimeImmutable());

        self::assertContains('bot', $decision->score->signalNames());
    }

    public function testAClientClaimingToBeABrowserWithoutBrowserHeadersIsFlagged(): void
    {
        $decision = $this->engine()->evaluate(
            new RiskContext(
                IpAddress::fromString('203.0.113.10'),
                UserAgent::fromString(self::BROWSER),
                'POST',
                '/api/v1/auth/login',
                [], // no accept, no accept-language
            ),
            new \DateTimeImmutable(),
        );

        self::assertContains('automation', $decision->score->signalNames());
    }

    public function testKnownSearchCrawlersAreScoredLowRatherThanBlocked(): void
    {
        $decision = $this->engine()->evaluate(
            $this->context(userAgent: 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'),
            new \DateTimeImmutable(),
        );

        self::assertSame(RiskAction::Allow, $decision->action, 'an anime index wants to be indexed');
    }

    /**
     * Corroboration, not a single noisy detector, is what reaches Block. This is
     * why signals are additive and capped rather than multiplicative.
     */
    public function testSignalsAccumulateTowardsHarderActions(): void
    {
        $decision = $this->engine()->evaluate(
            (new RiskContext(
                IpAddress::fromString('203.0.113.10'),
                UserAgent::fromString('python-requests/2.32'),
                'POST',
                '/api/v1/auth/login',
                ['via' => '1.1 someproxy'],
            ))->withCounters(recentFailedLogins: 12, requestsInWindow: 400),
            new \DateTimeImmutable(),
        );

        self::assertSame(100, $decision->score->value);
        self::assertSame(RiskAction::Block, $decision->action);
        self::assertTrue($decision->shouldAudit());
    }

    public function testForwardingHeadersOurEdgeDoesNotSetAreASignal(): void
    {
        $decision = $this->engine()->evaluate(
            new RiskContext(
                IpAddress::fromString('203.0.113.10'),
                UserAgent::fromString(self::BROWSER),
                'GET',
                '/api/v1/features',
                ['accept' => '*/*', 'accept-language' => 'hu', 'client-ip' => '1.2.3.4'],
            ),
            new \DateTimeImmutable(),
        );

        self::assertContains('proxy', $decision->score->signalNames());
    }

    private function engine(?Ban $ban = null): RiskEngine
    {
        $bans = new class($ban) implements BanRepositoryInterface {
            public function __construct(private readonly ?Ban $ban)
            {
            }

            public function findActiveFor(IpAddress $ip, ?UserId $userId, \DateTimeImmutable $now): ?Ban
            {
                return $this->ban;
            }

            public function save(Ban $ban): void
            {
            }

            public function lift(string $banId, \DateTimeImmutable $now): void
            {
            }

            public function listActive(\DateTimeImmutable $now, int $limit = 100): array
            {
                return [];
            }
        };

        return new RiskEngine([new BotDetector(), new AutomationDetector(), new ProxyDetector()], $bans);
    }

    private function context(
        string $userAgent = self::BROWSER,
        string $path = '/api/v1/features',
    ): RiskContext {
        return new RiskContext(
            IpAddress::fromString('203.0.113.10'),
            UserAgent::fromString($userAgent),
            'GET',
            $path,
            ['accept' => 'application/json', 'accept-language' => 'hu-HU'],
        );
    }
}
