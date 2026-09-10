<?php

declare(strict_types=1);

namespace Yume\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yume\Api\Domain\Auth\Exception\WeakPasswordException;
use Yume\Api\Domain\Auth\Service\PasswordPolicy;

final class PasswordPolicyTest extends TestCase
{
    private PasswordPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new PasswordPolicy();
    }

    public function testAReasonablePassphraseIsAccepted(): void
    {
        $this->policy->assertAcceptable('correct horse battery staple');

        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('unacceptablePasswords')]
    public function testUnacceptablePasswordsAreRejected(string $password, string $expectedReason): void
    {
        try {
            $this->policy->assertAcceptable($password);
            self::fail('Expected the password to be rejected: ' . $password);
        } catch (WeakPasswordException $e) {
            self::assertSame($expectedReason, $e->context()['reason']);
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function unacceptablePasswords(): iterable
    {
        yield 'too short' => ['short1234', 'too_short'];
        yield 'common word' => ['mypasswordisgood', 'blocklisted'];
        yield 'hungarian common word' => ['ajelszohelyesen', 'blocklisted'];
        yield 'site name' => ['animemforever123', 'blocklisted'];
        yield 'repeated character' => ['aaaaaaaaaaaaaaaa', 'predictable'];
        yield 'sequential' => ['abcdefghijklmnop', 'predictable'];
    }

    public function testPasswordMayNotContainTheUsernameOrEmail(): void
    {
        $this->expectException(WeakPasswordException::class);

        $this->policy->assertAcceptable('kitsune-is-my-secret', ['kitsune', 'kitsune@yume.local']);
    }

    /**
     * Argon2id over an unbounded input is a cheap denial of service, so the
     * upper bound is a security control rather than a convenience.
     */
    public function testAbsurdlyLongPasswordsAreRejectedBeforeHashing(): void
    {
        $this->expectException(WeakPasswordException::class);

        $this->policy->assertAcceptable(str_repeat('xK9#mQ2v', 1000));
    }

    /**
     * The legacy login ran the plaintext through htmlspecialchars() and
     * strip_tags() before hashing, so `<my>&secret"passphrase` silently became
     * something else. Special characters must survive untouched.
     */
    public function testSpecialCharactersAreNotTreatedAsMarkup(): void
    {
        $this->policy->assertAcceptable('<my>&"secret\'passphrase');

        $this->expectNotToPerformAssertions();
    }
}
