<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class CreateEpisodeCommand implements CommandInterface
{
    public function __construct(
        public string $animeId,
        public int $number,
        public ?string $title = null,
        public ?string $actingUserId = null,
    ) {
    }
}
