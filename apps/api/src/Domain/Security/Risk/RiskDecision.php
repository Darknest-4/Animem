<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Risk;

final readonly class RiskDecision
{
    public function __construct(
        public RiskAction $action,
        public RiskScore $score,
        public string $reason,
    ) {
    }

    public function shouldAudit(): bool
    {
        return $this->action !== RiskAction::Allow;
    }
}
