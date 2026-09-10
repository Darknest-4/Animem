<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Detection;

use Yume\Api\Domain\Security\Risk\RiskContext;
use Yume\Api\Domain\Security\Risk\RiskSignal;

/**
 * Tor is not treated as hostile in itself — plenty of legitimate users have
 * good reasons for it — but it does raise the score on sensitive paths.
 */
final class TorDetector implements DetectorInterface
{
    public function __construct(private readonly NetworkReputationRepositoryInterface $reputation)
    {
    }

    public function name(): string
    {
        return 'tor';
    }

    public function detect(RiskContext $context): ?RiskSignal
    {
        if (!$this->reputation->isTorExitNode($context->ip)) {
            return null;
        }

        return new RiskSignal(
            $this->name(),
            $context->isSensitivePath() ? 30 : 10,
            'Request originates from a Tor exit node.',
        );
    }
}
