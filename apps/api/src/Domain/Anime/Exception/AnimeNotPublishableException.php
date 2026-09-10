<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class AnimeNotPublishableException extends DomainException
{
    /** @param list<string> $missingFields */
    public function __construct(private readonly array $missingFields)
    {
        parent::__construct('This entry is not complete enough to publish.');
    }

    public function errorCode(): string
    {
        return 'anime.not_publishable';
    }

    public function context(): array
    {
        return ['missing_fields' => $this->missingFields];
    }
}
