<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Risk;

/**
 * An additive score capped at 100.
 *
 * Signals are additive rather than multiplicative so a single noisy detector can
 * never on its own push a request into Block; that takes corroboration.
 */
final readonly class RiskScore
{
    /** @param list<RiskSignal> $signals */
    private function __construct(public int $value, public array $signals)
    {
    }

    /** @param list<RiskSignal> $signals */
    public static function fromSignals(array $signals): self
    {
        $total = 0;
        foreach ($signals as $signal) {
            $total += $signal->weight;
        }

        return new self(min(100, $total), $signals);
    }

    public static function zero(): self
    {
        return new self(0, []);
    }

    /** @return list<string> */
    public function signalNames(): array
    {
        return array_map(static fn (RiskSignal $s): string => $s->name, $this->signals);
    }

    /** @return list<array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(static fn (RiskSignal $s): array => $s->toArray(), $this->signals);
    }
}
