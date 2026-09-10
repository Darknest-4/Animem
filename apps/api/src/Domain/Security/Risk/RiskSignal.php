<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Risk;

/** One observation contributing to a risk score. */
final readonly class RiskSignal
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public string $name,
        public int $weight,
        public string $reason,
        public array $evidence = [],
    ) {
        if ($weight < 0 || $weight > 100) {
            throw new \InvalidArgumentException('Risk signal weight must be between 0 and 100.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'weight' => $this->weight,
            'reason' => $this->reason,
            'evidence' => $this->evidence,
        ];
    }
}
