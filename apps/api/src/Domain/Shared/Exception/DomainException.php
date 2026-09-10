<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Shared\Exception;

/**
 * Base class for every expected business-rule violation.
 *
 * Carries an HTTP status and a stable machine-readable code so the presentation
 * layer can translate domain failures without knowing what they mean.
 */
abstract class DomainException extends \RuntimeException
{
    /** @return array<string, mixed> */
    public function context(): array
    {
        return [];
    }

    abstract public function errorCode(): string;

    public function httpStatus(): int
    {
        return 422;
    }
}
