<?php

declare(strict_types=1);

namespace Yume\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yume\Api\Domain\Feature\Entity\FeatureFlag;
use Yume\Api\Domain\Feature\ValueObject\FlagKey;
use Yume\Api\Domain\Feature\ValueObject\RolloutStrategy;

final class FeatureFlagTest extends TestCase
{
    public function testOffAndOnAreAbsolute(): void
    {
        self::assertFalse($this->flag(RolloutStrategy::Off, 100)->isEnabledFor(['user_id' => 'u1']));
        self::assertTrue($this->flag(RolloutStrategy::On, 0)->isEnabledFor([]));
    }

    /**
     * The property that makes a percentage rollout safe: a user who is inside the
     * cohort at 10% must still be inside it at 50%. Otherwise widening a rollout
     * would move users *out* of the new experience.
     */
    public function testPercentageBucketingIsMonotonicAsRolloutWidens(): void
    {
        $enabledAtTen = [];

        for ($i = 0; $i < 400; ++$i) {
            if ($this->flag(RolloutStrategy::Percentage, 10)->isEnabledFor(['user_id' => 'user-' . $i])) {
                $enabledAtTen[] = 'user-' . $i;
            }
        }

        self::assertNotEmpty($enabledAtTen, 'a 10% rollout over 400 users should select someone');

        foreach ($enabledAtTen as $userId) {
            self::assertTrue(
                $this->flag(RolloutStrategy::Percentage, 50)->isEnabledFor(['user_id' => $userId]),
                sprintf('%s was in the 10%% cohort but fell out of the 50%% cohort', $userId),
            );
        }
    }

    public function testPercentageBucketingIsStableForTheSameActor(): void
    {
        $flag = $this->flag(RolloutStrategy::Percentage, 50);

        $first = $flag->isEnabledFor(['user_id' => 'stable-user']);

        for ($i = 0; $i < 20; ++$i) {
            self::assertSame($first, $flag->isEnabledFor(['user_id' => 'stable-user']));
        }
    }

    /**
     * Two flags at the same percentage must not select the same people, or every
     * experiment would run on one unlucky cohort.
     */
    public function testDifferentFlagsSelectDifferentCohorts(): void
    {
        $a = FeatureFlag::reconstitute(
            FlagKey::fromString('flag_alpha'),
            'Alpha',
            null,
            RolloutStrategy::Percentage,
            50,
            [],
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
        );

        $b = FeatureFlag::reconstitute(
            FlagKey::fromString('flag_beta'),
            'Beta',
            null,
            RolloutStrategy::Percentage,
            50,
            [],
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
        );

        $differences = 0;
        for ($i = 0; $i < 200; ++$i) {
            $context = ['user_id' => 'user-' . $i];
            if ($a->isEnabledFor($context) !== $b->isEnabledFor($context)) {
                ++$differences;
            }
        }

        self::assertGreaterThan(50, $differences, 'two 50% flags should disagree on roughly half the population');
    }

    public function testAnonymousActorsAreNeverInAPercentageCohort(): void
    {
        self::assertFalse($this->flag(RolloutStrategy::Percentage, 99)->isEnabledFor([]));
    }

    public function testRoleStrategyMatchesAnyGrantedRole(): void
    {
        $flag = $this->flag(RolloutStrategy::Role, 0, ['roles' => ['moderator', 'admin']]);

        self::assertTrue($flag->isEnabledFor(['roles' => ['user', 'moderator']]));
        self::assertFalse($flag->isEnabledFor(['roles' => ['user']]));
        self::assertFalse($flag->isEnabledFor([]));
    }

    public function testIpListReplacesTheLegacyHardcodedAddressCheck(): void
    {
        // html/Newindex.php:22 gated a whole page on `$ip != "84.0.6.47"`.
        $flag = $this->flag(RolloutStrategy::IpList, 0, ['ips' => ['84.0.6.47']]);

        self::assertTrue($flag->isEnabledFor(['ip' => '84.0.6.47']));
        self::assertFalse($flag->isEnabledFor(['ip' => '84.0.6.48']));
    }

    public function testAnExpiredFlagStillEvaluatesRatherThanFlippingUnderTraffic(): void
    {
        $flag = FeatureFlag::reconstitute(
            FlagKey::fromString('stale_flag'),
            'Stale',
            null,
            RolloutStrategy::On,
            100,
            [],
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-02-01'),
        );

        self::assertTrue($flag->isExpired(new \DateTimeImmutable('2026-03-01')));
        self::assertTrue($flag->isEnabledFor([]), 'expiry reports staleness, it must not silently disable the flag');
    }

    /** @param array<string, mixed> $payload */
    private function flag(RolloutStrategy $strategy, int $percentage, array $payload = []): FeatureFlag
    {
        return FeatureFlag::reconstitute(
            FlagKey::fromString('test_flag'),
            'Test flag',
            null,
            $strategy,
            $percentage,
            $payload,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
        );
    }
}
