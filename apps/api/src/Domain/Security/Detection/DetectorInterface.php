<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Detection;

use Yume\Api\Domain\Security\Risk\RiskContext;
use Yume\Api\Domain\Security\Risk\RiskSignal;

/**
 * A detector inspects one request and either stays silent or emits a signal.
 *
 * Detectors must be pure and fast: they run on every request, before routing.
 * Anything needing I/O belongs behind a repository injected into the detector,
 * with its own timeout.
 */
interface DetectorInterface
{
    public function name(): string;

    public function detect(RiskContext $context): ?RiskSignal;
}
