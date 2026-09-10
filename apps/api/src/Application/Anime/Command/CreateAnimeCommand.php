<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Command;

use Yume\Contracts\Bus\CommandInterface;

/**
 * Creates a draft. Publishing is a separate, explicitly permissioned step, so an
 * incomplete entry cannot reach the public catalogue by accident.
 */
final readonly class CreateAnimeCommand implements CommandInterface
{
    public function __construct(
        public string $title,
        public string $mediaType,
        public ?string $actingUserId = null,
        public ?int $malId = null,
    ) {
    }
}
