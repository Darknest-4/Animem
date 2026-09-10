<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Risk;

use Yume\Api\Domain\Security\Ban\BanRepositoryInterface;
use Yume\Api\Domain\Security\Detection\DetectorInterface;

/**
 * Turns request observations into one of six graded actions.
 *
 * Design notes:
 *  - An active ban short-circuits everything; it is a decision already made by a
 *    human or by a previous automated escalation, not another signal to weigh.
 *  - Thresholds are configuration, not constants, so they can be tuned per
 *    environment without a code change.
 *  - The engine never performs I/O itself. Detectors that need data receive a
 *    repository; that keeps this class unit-testable with no database.
 */
final class RiskEngine
{
    /**
     * @param list<DetectorInterface> $detectors
     * @param array<string, int> $thresholds action name => minimum score
     */
    public function __construct(
        private readonly array $detectors,
        private readonly BanRepositoryInterface $bans,
        private readonly array $thresholds = [
            'monitor' => 20,
            'rate_limit' => 40,
            'challenge' => 60,
            'restrict' => 75,
            'block' => 90,
        ],
    ) {
    }

    public function evaluate(RiskContext $context, \DateTimeImmutable $now): RiskDecision
    {
        $ban = $this->bans->findActiveFor($context->ip, $context->userId, $now);
        if ($ban !== null) {
            return new RiskDecision(
                RiskAction::Block,
                RiskScore::fromSignals([new RiskSignal('ban', 100, $ban->reason, ['ban_id' => $ban->id])]),
                sprintf('Active %s ban.', $ban->scope->value),
            );
        }

        $signals = [];
        foreach ($this->detectors as $detector) {
            $signal = $detector->detect($context);
            if ($signal instanceof RiskSignal) {
                $signals[] = $signal;
            }
        }

        foreach ($this->behaviouralSignals($context) as $signal) {
            $signals[] = $signal;
        }

        $score = RiskScore::fromSignals($signals);

        return new RiskDecision(
            $this->actionFor($score->value),
            $score,
            $signals === [] ? 'No risk signals.' : implode('; ', array_map(
                static fn (RiskSignal $s): string => $s->reason,
                $signals,
            )),
        );
    }

    /** @return list<RiskSignal> */
    private function behaviouralSignals(RiskContext $context): array
    {
        $signals = [];

        if ($context->recentFailedLogins >= 10) {
            $signals[] = new RiskSignal(
                'credential_stuffing',
                50,
                'Many recent failed logins from this address.',
                ['failed_logins' => $context->recentFailedLogins],
            );
        } elseif ($context->recentFailedLogins >= 4) {
            $signals[] = new RiskSignal(
                'failed_logins',
                20,
                'Repeated failed logins from this address.',
                ['failed_logins' => $context->recentFailedLogins],
            );
        }

        if ($context->requestsInWindow >= 300) {
            $signals[] = new RiskSignal(
                'request_flood',
                40,
                'Request rate far above normal for a browser.',
                ['requests' => $context->requestsInWindow],
            );
        }

        return $signals;
    }

    private function actionFor(int $score): RiskAction
    {
        return match (true) {
            $score >= $this->thresholds['block'] => RiskAction::Block,
            $score >= $this->thresholds['restrict'] => RiskAction::Restrict,
            $score >= $this->thresholds['challenge'] => RiskAction::Challenge,
            $score >= $this->thresholds['rate_limit'] => RiskAction::RateLimit,
            $score >= $this->thresholds['monitor'] => RiskAction::Monitor,
            default => RiskAction::Allow,
        };
    }
}
