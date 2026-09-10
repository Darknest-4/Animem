<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class PublishEpisodeCommand implements CommandInterface
{
    public function __construct(
        public string $episodeId,
        public bool $published,
        public ?string $actingUserId = null,
    ) {
    }
}
