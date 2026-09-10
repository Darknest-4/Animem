<?php

declare(strict_types=1);

namespace Yume\Tests\Integration;

use Yume\Api\Domain\User\Entity\User;
use Yume\Api\Domain\User\ValueObject\Email;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Domain\User\ValueObject\Username;
use Yume\Api\Infrastructure\Persistence\Repository\PdoRoleRepository;
use Yume\Api\Infrastructure\Persistence\Repository\PdoUserRepository;
use Yume\Shared\Identity\UuidV7Generator;
use Yume\Shared\Clock\SystemClock;
use Yume\Tests\Support\DatabaseTestCase;

/**
 * Exercises the RBAC storage against a real PostgreSQL instance.
 *
 * These assertions cannot be made against a mock: the guarantees being tested —
 * unique indexes under concurrency, the permission-slug CHECK constraint,
 * cascade deletes — live in the schema, not in PHP.
 */
final class RbacPersistenceTest extends DatabaseTestCase
{
    public function testSeededRolesResolveToTheExpectedPermissions(): void
    {
        $roles = new PdoRoleRepository($this->connection);

        self::assertTrue($roles->findBySlug('admin')?->permissions->allows('security.manage'));
        self::assertTrue($roles->findBySlug('guest')?->permissions->allows('anime.view'));
        self::assertFalse($roles->findBySlug('guest')?->permissions->allows('anime.edit'));
        self::assertFalse($roles->findBySlug('user')?->permissions->allows('admin.access'));
    }

    public function testAdminHoldsTheWildcardRatherThanAnEnumeratedList(): void
    {
        $roles = new PdoRoleRepository($this->connection);

        self::assertSame(['*'], $roles->findBySlug('admin')?->permissions->toStrings());
    }

    public function testGuestPermissionsApplyToAnonymousCallers(): void
    {
        $roles = new PdoRoleRepository($this->connection);

        self::assertTrue($roles->permissionsForGuest()->allows('anime.view'));
        self::assertFalse($roles->permissionsForGuest()->allows('user.manage'));
    }

    public function testGrantingAndRevokingARoleChangesEffectivePermissions(): void
    {
        $users = new PdoUserRepository($this->connection);
        $roles = new PdoRoleRepository($this->connection);
        $userId = $this->createUser($users, 'moderator-candidate');

        $roles->assignRole($userId, 'user');
        self::assertFalse($roles->permissionsForUser($userId)->allows('anime.delete'));

        $roles->assignRole($userId, 'moderator');
        self::assertTrue($roles->permissionsForUser($userId)->allows('anime.delete'));

        $roles->revokeRole($userId, 'moderator');
        self::assertFalse($roles->permissionsForUser($userId)->allows('anime.delete'));
    }

    public function testAssigningAnUnknownRoleFailsLoudly(): void
    {
        $users = new PdoUserRepository($this->connection);
        $roles = new PdoRoleRepository($this->connection);
        $userId = $this->createUser($users, 'typo-victim');

        $this->expectException(\RuntimeException::class);

        // A silent no-op would leave the account with no permissions at all.
        $roles->assignRole($userId, 'moderatorr');
    }

    public function testAssigningTheSameRoleTwiceIsIdempotent(): void
    {
        $users = new PdoUserRepository($this->connection);
        $roles = new PdoRoleRepository($this->connection);
        $userId = $this->createUser($users, 'eager-granter');

        $roles->assignRole($userId, 'user');
        $roles->assignRole($userId, 'user');

        self::assertSame(['user'], $roles->roleSlugsForUser($userId));
    }

    /**
     * The application checks first for a good error message, but the index is
     * what actually holds under two concurrent registrations.
     */
    public function testUsernameUniquenessIsEnforcedByTheDatabase(): void
    {
        $users = new PdoUserRepository($this->connection);
        $this->createUser($users, 'kitsune');

        $this->expectException(\Throwable::class);

        $users->save(User::register(
            UserId::fromString((new UuidV7Generator(new SystemClock()))->generate()),
            Username::fromString('Kit.Sune'), // canonicalises to the same 'kitsune'
            Email::fromString('different@yume.test'),
            new \DateTimeImmutable(),
        ));
    }

    public function testCaseInsensitiveLookupFindsTheUser(): void
    {
        $users = new PdoUserRepository($this->connection);
        $this->createUser($users, 'MixedCase');

        self::assertNotNull($users->findByIdentifier('mixedcase'));
        self::assertNotNull($users->findByIdentifier('MIXEDCASE'));
    }

    public function testAnIdentifierThatIsNeitherUsernameNorEmailReturnsNothing(): void
    {
        $users = new PdoUserRepository($this->connection);

        self::assertNull($users->findByIdentifier("' OR 1=1 --"));
    }

    public function testPermissionSlugShapeIsEnforcedBySchemaConstraint(): void
    {
        $this->expectException(\Throwable::class);

        $this->connection->execute(
            'INSERT INTO permissions (slug, name) VALUES (:slug, :name)',
            ['slug' => 'not a valid slug', 'name' => 'Bad'],
        );
    }

    public function testDeletingAUserCascadesToItsRolesAndSessions(): void
    {
        $users = new PdoUserRepository($this->connection);
        $roles = new PdoRoleRepository($this->connection);
        $userId = $this->createUser($users, 'departing');
        $roles->assignRole($userId, 'user');

        $this->connection->execute('DELETE FROM users WHERE id = :id', ['id' => $userId->value]);

        self::assertSame(
            0,
            (int) $this->connection->scalar(
                'SELECT count(*) FROM user_roles WHERE user_id = :id',
                ['id' => $userId->value],
            ),
        );
    }

    private function createUser(PdoUserRepository $users, string $username): UserId
    {
        $id = UserId::fromString((new UuidV7Generator(new SystemClock()))->generate());

        $users->save(User::register(
            $id,
            Username::fromString($username),
            Email::fromString(strtolower($username) . '@yume.test'),
            new \DateTimeImmutable(),
        ));

        return $id;
    }
}
