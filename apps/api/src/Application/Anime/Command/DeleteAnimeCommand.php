<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class DeleteAnimeCommand implements CommandInterface
{
    public function __construct(
        public string $animeId,
        public string $ip,
        public string $userAgent,
        public ?string $actingUserId = null,
    ) {
    }
}
