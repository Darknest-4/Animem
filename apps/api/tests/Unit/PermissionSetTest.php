<?php

declare(strict_types=1);

namespace Yume\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yume\Api\Domain\Authorization\ValueObject\PermissionSet;
use Yume\Api\Domain\Authorization\ValueObject\PermissionSlug;

final class PermissionSetTest extends TestCase
{
    public function testExactMatchIsGranted(): void
    {
        $set = PermissionSet::fromStrings(['anime.edit', 'episode.view']);

        self::assertTrue($set->allows('anime.edit'));
        self::assertFalse($set->allows('anime.delete'));
    }

    public function testResourceWildcardGrantsEveryActionOnThatResourceOnly(): void
    {
        $set = PermissionSet::fromStrings(['anime.*']);

        self::assertTrue($set->allows('anime.edit'));
        self::assertTrue($set->allows('anime.delete'));
        self::assertFalse($set->allows('episode.edit'), 'anime.* must not leak into another resource');
    }

    public function testGlobalWildcardGrantsEverything(): void
    {
        $set = PermissionSet::fromStrings(['*']);

        self::assertTrue($set->allows('anime.delete'));
        self::assertTrue($set->allows('security.manage'));
    }

    public function testEmptySetGrantsNothing(): void
    {
        self::assertFalse(PermissionSet::empty()->allows('anime.view'));
    }

    /**
     * The dangerous mistake would be a route asking for `*` and every role
     * satisfying it. Requiring a wildcard is refused outright.
     */
    public function testARouteMayNotRequireAWildcard(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PermissionSlug::required('anime.*');
    }

    public function testAllowsAllRequiresEveryPermission(): void
    {
        $set = PermissionSet::fromStrings(['admin.access']);

        self::assertFalse($set->allowsAll(['admin.access', 'user.view']));
        self::assertTrue($set->allowsAll(['admin.access']));
    }

    public function testDuplicatesAndCasingAreNormalised(): void
    {
        $set = PermissionSet::fromStrings(['Anime.View', 'anime.view', 'ANIME.VIEW']);

        self::assertCount(1, $set);
        self::assertTrue($set->allows('anime.view'));
    }

    #[DataProvider('malformedSlugs')]
    public function testMalformedSlugsAreRejected(string $slug): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PermissionSlug::fromString($slug);
    }

    /** @return iterable<string, array{string}> */
    public static function malformedSlugs(): iterable
    {
        yield 'no action' => ['anime'];
        yield 'three segments' => ['anime.episode.edit'];
        yield 'leading digit' => ['1anime.view'];
        yield 'spaces' => ['anime .view'];
        yield 'empty' => [''];
        yield 'sql-ish' => ["anime.view'; DROP TABLE users;--"];
    }
}
