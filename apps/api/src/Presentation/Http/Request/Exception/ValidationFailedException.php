<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Request\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class ValidationFailedException extends DomainException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('The submitted data is invalid.');
    }

    public function errorCode(): string
    {
        return 'validation.failed';
    }

    public function context(): array
    {
        return ['errors' => $this->errors];
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
