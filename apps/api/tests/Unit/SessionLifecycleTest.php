<?php

declare(strict_types=1);

namespace Yume\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yume\Api\Domain\Auth\Entity\Session;
use Yume\Api\Domain\Auth\Service\SessionPolicy;
use Yume\Api\Domain\Auth\ValueObject\SessionId;
use Yume\Api\Domain\Auth\ValueObject\TokenHash;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Shared\Clock\FrozenClock;

final class SessionLifecycleTest extends TestCase
{
    private const BROWSER = 'Mozilla/5.0 (X11; Linux x86_64) Chrome/140.0';

    public function testASessionExpiresAtItsAbsoluteLifetime(): void
    {
        $clock = new FrozenClock();
        $session = $this->session($clock->now(), lifetime: 3600);

        self::assertTrue($session->isActive($clock->now()));

        $clock->advance(3601);
        self::assertFalse($session->isActive($clock->now()));
        self::assertTrue($session->isExpired($clock->now()));
    }

    public function testTouchingExtendsTheExpiryWithinTheSlidingWindow(): void
    {
        $clock = new FrozenClock();
        $session = $this->session($clock->now(), lifetime: 3600);

        $clock->advance(1800);
        $session->touch($clock->now(), idleExtensionSeconds: 7200);

        $clock->advance(3600);
        self::assertTrue($session->isActive($clock->now()), 'the slide should have pushed expiry past the original lifetime');
    }

    public function testTouchNeverShortensAnExpiry(): void
    {
        $clock = new FrozenClock();
        $session = $this->session($clock->now(), lifetime: 86400);
        $before = $session->expiresAt();

        $session->touch($clock->now(), idleExtensionSeconds: 60);

        self::assertEquals($before, $session->expiresAt());
    }

    public function testRevocationIsImmediateAndIdempotent(): void
    {
        $clock = new FrozenClock();
        $session = $this->session($clock->now());

        $session->revoke($clock->now(), 'user_logout');
        $firstRevokedAt = $session->revokedAt();

        $clock->advance(60);
        $session->revoke($clock->now(), 'something_else');

        self::assertFalse($session->isActive($clock->now()));
        self::assertEquals($firstRevokedAt, $session->revokedAt(), 'the original revocation time is the audit record');
        self::assertSame('user_logout', $session->revokedReason());
    }

    public function testTokenRotationKeepsTheSessionIdentityAndAuditTrail(): void
    {
        $clock = new FrozenClock();
        $session = $this->session($clock->now());
        $originalId = $session->id->value;
        $originalHash = $session->tokenHash()->value;

        $session->rotateToken(TokenHash::fromString(str_repeat('b', 64)));

        self::assertSame($originalId, $session->id->value);
        self::assertNotSame($originalHash, $session->tokenHash()->value);
    }

    public function testAChangedUserAgentIsTreatedAsAStolenSession(): void
    {
        $policy = new SessionPolicy();
        $session = $this->session(new \DateTimeImmutable());

        self::assertTrue($policy->looksHijacked(
            $session,
            IpAddress::fromString('203.0.113.9'),
            UserAgent::fromString('curl/8.5.0'),
        ));
    }

    /**
     * Mobile networks change IP constantly. Treating that as theft would sign
     * users out for walking between cells.
     */
    public function testAChangedIpAloneIsNotTreatedAsTheft(): void
    {
        $policy = new SessionPolicy();
        $session = $this->session(new \DateTimeImmutable());

        self::assertFalse($policy->looksHijacked(
            $session,
            IpAddress::fromString('203.0.113.250'),
            UserAgent::fromString(self::BROWSER),
        ));
    }

    public function testRotationIsDueOncePastTheRotationInterval(): void
    {
        $clock = new FrozenClock();
        $policy = new SessionPolicy(rotateAfterSeconds: 3600);
        $session = $this->session($clock->now());

        self::assertFalse($policy->shouldRotateToken($session, $clock->now()));

        $clock->advance(3601);
        self::assertTrue($policy->shouldRotateToken($session, $clock->now()));
    }

    private function session(\DateTimeImmutable $now, int $lifetime = 86400): Session
    {
        return Session::start(
            SessionId::fromString('01a088ea-c29c-7d0e-9a5e-bc3820635696'),
            UserId::fromString('01a088ea-c40a-7d65-bf9f-860fbb68f600'),
            TokenHash::fromString(str_repeat('a', 64)),
            IpAddress::fromString('203.0.113.10'),
            UserAgent::fromString(self::BROWSER),
            $now,
            $lifetime,
        );
    }
}
