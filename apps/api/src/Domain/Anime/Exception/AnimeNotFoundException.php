<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Anime\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class AnimeNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('No anime entry "%s".', $id));
    }

    public function errorCode(): string
    {
        return 'anime.not_found';
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
