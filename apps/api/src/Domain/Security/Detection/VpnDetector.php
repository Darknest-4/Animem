<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Detection;

use Yume\Api\Domain\Security\Risk\RiskContext;
use Yume\Api\Domain\Security\Risk\RiskSignal;

final class VpnDetector implements DetectorInterface
{
    public function __construct(private readonly NetworkReputationRepositoryInterface $reputation)
    {
    }

    public function name(): string
    {
        return 'vpn';
    }

    public function detect(RiskContext $context): ?RiskSignal
    {
        if ($this->reputation->isKnownVpn($context->ip)) {
            return new RiskSignal($this->name(), 10, 'Request originates from a known VPN range.');
        }

        if ($this->reputation->isDatacentre($context->ip)) {
            // Residential traffic is expected for a streaming index; a datacentre
            // IP posting to /auth/login is not.
            return new RiskSignal(
                $this->name(),
                $context->isSensitivePath() ? 25 : 8,
                'Request originates from a datacentre network.',
                $this->reputation->lookupAsn($context->ip) ?? [],
            );
        }

        return null;
    }
}
