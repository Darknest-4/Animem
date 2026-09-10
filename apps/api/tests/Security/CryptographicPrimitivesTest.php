<?php

declare(strict_types=1);

namespace Yume\Tests\Security;

use PHPUnit\Framework\TestCase;
use Yume\Security\Csrf\CsrfTokenManager;
use Yume\Security\Password\PasswordHasher;
use Yume\Security\Token\TokenGenerator;

final class CryptographicPrimitivesTest extends TestCase
{
    public function testArgon2idRoundTrip(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('a long enough passphrase');

        self::assertStringStartsWith('$argon2id$', $hash);
        self::assertTrue($hasher->verify('a long enough passphrase', $hash));
        self::assertFalse($hasher->verify('wrong', $hash));
    }

    public function testEachHashIsSaltedDifferently(): void
    {
        $hasher = new PasswordHasher();

        self::assertNotSame(
            $hasher->hash('same passphrase here'),
            $hasher->hash('same passphrase here'),
            'identical hashes would mean no salt, which is what the legacy SHA-256 column had',
        );
    }

    /**
     * The migration path off the legacy `hash("sha256", $password)` column: it
     * must verify once so the owner can log in, and must be replaceable.
     */
    public function testLegacySha256VerifiesForUpgradeOnly(): void
    {
        $hasher = new PasswordHasher();

        self::assertTrue($hasher->verifyLegacySha256('secret', hash('sha256', 'secret')));
        self::assertFalse($hasher->verifyLegacySha256('other', hash('sha256', 'secret')));
    }

    /**
     * The legacy login did htmlspecialchars(strip_tags($password)) before hashing,
     * so a password containing < > & or " was silently altered.
     */
    public function testSpecialCharactersSurviveHashingUntouched(): void
    {
        $hasher = new PasswordHasher();
        $password = '<my>&"secret\'passphrase';

        self::assertTrue($hasher->verify($password, $hasher->hash($password)));
    }

    public function testNeedsRehashDetectsWeakerParameters(): void
    {
        $weak = new PasswordHasher(PASSWORD_ARGON2ID, ['memory_cost' => 16384, 'time_cost' => 2, 'threads' => 1]);
        $strong = new PasswordHasher();

        self::assertTrue($strong->needsRehash($weak->hash('a long enough passphrase')));
    }

    public function testSessionTokensAreStoredOnlyAsADigest(): void
    {
        $token = (new TokenGenerator())->generate();

        self::assertNotSame($token->plain, $token->hash);
        self::assertSame(64, strlen($token->hash));
        self::assertTrue(TokenGenerator::matches($token->plain, $token->hash));
        self::assertFalse(TokenGenerator::matches('something else', $token->hash));
    }

    public function testSessionTokensAreUnpredictable(): void
    {
        $generator = new TokenGenerator();
        $tokens = [];

        for ($i = 0; $i < 200; ++$i) {
            $tokens[] = $generator->generate()->plain;
        }

        self::assertCount(200, array_unique($tokens));
    }

    public function testCsrfTokenIsBoundToItsOwnSession(): void
    {
        $manager = new CsrfTokenManager(str_repeat('k', 32));
        $token = $manager->generate('session-a');

        self::assertTrue($manager->isValid('session-a', $token));
        self::assertFalse($manager->isValid('session-b', $token), 'a token must not be replayable into another session');
    }

    public function testCsrfRejectsMissingAndTamperedTokens(): void
    {
        $manager = new CsrfTokenManager(str_repeat('k', 32));

        self::assertFalse($manager->isValid('session-a', null));
        self::assertFalse($manager->isValid('session-a', 'garbage'));
        self::assertFalse($manager->isValid('session-a', 'abc.def'));
    }

    public function testAShortCsrfSecretIsRefusedAtConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CsrfTokenManager('too-short');
    }
}
