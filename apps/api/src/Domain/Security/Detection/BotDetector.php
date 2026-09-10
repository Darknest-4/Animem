<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Detection;

use Yume\Api\Domain\Security\Risk\RiskContext;
use Yume\Api\Domain\Security\Risk\RiskSignal;

/**
 * Flags obvious automation by user agent.
 *
 * Well-behaved crawlers are scored low: the goal is to rate-limit them, not to
 * block Googlebot off an anime index that wants to be indexed.
 */
final class BotDetector implements DetectorInterface
{
    private const KNOWN_CRAWLERS = ['googlebot', 'bingbot', 'duckduckbot', 'applebot', 'yandexbot', 'baiduspider'];

    private const TOOLING = ['curl/', 'wget/', 'python-requests', 'python-urllib', 'go-http-client',
        'java/', 'okhttp', 'axios/', 'node-fetch', 'httpie', 'postmanruntime', 'scrapy', 'libwww-perl'];

    /** @param list<string> $allowedAgents user agents that are explicitly permitted */
    public function __construct(private readonly array $allowedAgents = [])
    {
    }

    public function name(): string
    {
        return 'bot';
    }

    public function detect(RiskContext $context): ?RiskSignal
    {
        $agent = strtolower($context->userAgent->value);

        if ($agent === '') {
            return new RiskSignal(
                $this->name(),
                25,
                'Request carries no User-Agent header.',
            );
        }

        foreach ($this->allowedAgents as $allowed) {
            if (str_contains($agent, strtolower($allowed))) {
                return null;
            }
        }

        foreach (self::KNOWN_CRAWLERS as $crawler) {
            if (str_contains($agent, $crawler)) {
                return new RiskSignal($this->name(), 5, 'Known search crawler.', ['crawler' => $crawler]);
            }
        }

        foreach (self::TOOLING as $tool) {
            if (str_contains($agent, $tool)) {
                return new RiskSignal(
                    $this->name(),
                    // Scripted clients hitting login or admin are far more interesting
                    // than the same client reading a public anime page.
                    $context->isSensitivePath() ? 45 : 20,
                    'User-Agent identifies a scripted HTTP client.',
                    ['tool' => $tool],
                );
            }
        }

        if (mb_strlen($agent) < 16) {
            return new RiskSignal($this->name(), 15, 'User-Agent is implausibly short.');
        }

        return null;
    }
}
