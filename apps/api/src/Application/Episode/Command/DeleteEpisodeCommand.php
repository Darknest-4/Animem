<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class DeleteEpisodeCommand implements CommandInterface
{
    public function __construct(
        public string $episodeId,
        public string $ip,
        public string $userAgent,
        public ?string $actingUserId = null,
    ) {
    }
}
