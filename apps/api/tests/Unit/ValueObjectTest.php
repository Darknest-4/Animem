<?php

declare(strict_types=1);

namespace Yume\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\User\ValueObject\Email;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Domain\User\ValueObject\Username;

final class ValueObjectTest extends TestCase
{
    /**
     * Duplicate-account detection needs a canonical form; without it
     * kit.sune+anime@gmail.com and kitsune@gmail.com are two accounts on one inbox.
     */
    public function testEmailCanonicalisationCollapsesGmailDotsAndPlusTags(): void
    {
        self::assertSame('kitsune@gmail.com', Email::fromString('Kit.Sune+anime@Gmail.com')->canonical());
        self::assertSame('kit.sune@yume.local', Email::fromString('Kit.Sune@Yume.local')->canonical());
    }

    /**
     * Separators are removed rather than normalised, so `kitsune`, `Kit.Sune`,
     * `kit-sune` and `kit_sune` are all the same account name. Impersonation by
     * near-identical spelling is the threat being closed here.
     */
    public function testUsernameCanonicalisationRemovesSeparatorsAndCase(): void
    {
        $canonical = 'kitsune';

        foreach (['kitsune', 'KitSune', 'Kit.Sune', 'kit-sune', 'kit_sune'] as $spelling) {
            self::assertSame($canonical, Username::fromString($spelling)->canonical(), $spelling);
        }
    }

    #[DataProvider('invalidUsernames')]
    public function testInvalidUsernamesAreRejected(string $username): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Username::fromString($username);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidUsernames(): iterable
    {
        yield 'too short' => ['ab'];
        yield 'leading underscore' => ['_kitsune'];
        yield 'trailing underscore' => ['kitsune_'];
        yield 'contains a space' => ['kit sune'];
        yield 'too long' => [str_repeat('x', 40)];
        yield 'markup' => ['<script>'];
    }

    /** Rate limiting by exact IP is defeated by rotating within a /24 or /64. */
    public function testSubnetKeysGroupNeighbouringAddresses(): void
    {
        self::assertSame('203.0.113.0/24', IpAddress::fromString('203.0.113.77')->subnetKey());
        self::assertSame(
            IpAddress::fromString('203.0.113.1')->subnetKey(),
            IpAddress::fromString('203.0.113.254')->subnetKey(),
        );
        self::assertStringEndsWith('::/64', IpAddress::fromString('2001:db8::1')->subnetKey());
    }

    public function testInvalidIpAddressesAreRejected(): void
    {
        self::assertNull(IpAddress::tryFromString('not-an-ip'));
        self::assertNull(IpAddress::tryFromString(''));
        self::assertNull(IpAddress::tryFromString('999.999.999.999'));
    }

    /**
     * The legacy `userID` cookie held an integer that went straight into SQL.
     * A UUID-only identifier removes both the enumeration and the injection shape.
     */
    public function testUserIdMustBeAUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UserId::fromString('1');
    }

    public function testUserAgentIsTruncatedRatherThanRejected(): void
    {
        $long = \Yume\Api\Domain\Shared\ValueObject\UserAgent::fromString(str_repeat('x', 5000));

        self::assertSame(512, mb_strlen($long->value));
    }
}
