<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Authorization\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class AccessDeniedException extends DomainException
{
    private function __construct(string $message, private readonly string $requiredPermission)
    {
        parent::__construct($message);
    }

    public static function missingPermission(string $permission): self
    {
        return new self('You do not have permission to perform this action.', $permission);
    }

    public function errorCode(): string
    {
        return 'authorization.denied';
    }

    public function context(): array
    {
        // Surfaced in logs and audit records; the HTTP body only carries the code.
        return ['required_permission' => $this->requiredPermission];
    }

    public function httpStatus(): int
    {
        return 403;
    }
}
