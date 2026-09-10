<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Command;

use Yume\Contracts\Bus\CommandInterface;

final readonly class AddEpisodeReleaseCommand implements CommandInterface
{
    public function __construct(
        public string $episodeId,
        public string $uploaderId,
        public string $url,
        public string $kind = 'sub',
        public string $language = 'hu',
        public ?string $actingUserId = null,
    ) {
    }
}
