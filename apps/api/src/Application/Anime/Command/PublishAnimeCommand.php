<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class PublishAnimeCommand implements CommandInterface
{
    public function __construct(
        public string $animeId,
        public bool $published,
        public ?string $actingUserId = null,
    ) {
    }
}
