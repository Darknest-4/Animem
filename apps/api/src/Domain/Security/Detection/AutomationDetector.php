<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Detection;

use Yume\Api\Domain\Security\Risk\RiskContext;
use Yume\Api\Domain\Security\Risk\RiskSignal;

/**
 * Looks for headless browsers and for the header shape a real browser always has.
 *
 * A genuine browser sends Accept and Accept-Language on a document request; a
 * hand-rolled script usually forgets at least one.
 */
final class AutomationDetector implements DetectorInterface
{
    private const HEADLESS_MARKERS = ['headlesschrome', 'phantomjs', 'electron/', 'puppeteer', 'playwright', 'selenium'];

    public function name(): string
    {
        return 'automation';
    }

    public function detect(RiskContext $context): ?RiskSignal
    {
        $agent = strtolower($context->userAgent->value);

        foreach (self::HEADLESS_MARKERS as $marker) {
            if (str_contains($agent, $marker)) {
                return new RiskSignal($this->name(), 40, 'Headless browser signature.', ['marker' => $marker]);
            }
        }

        $missing = [];
        foreach (['accept', 'accept-language'] as $header) {
            if ($context->header($header) === null) {
                $missing[] = $header;
            }
        }

        if ($missing !== [] && str_contains($agent, 'mozilla')) {
            // Claims to be a browser but does not send what browsers send.
            return new RiskSignal(
                $this->name(),
                30,
                'Client claims to be a browser but omits standard request headers.',
                ['missing_headers' => $missing],
            );
        }

        return null;
    }
}
