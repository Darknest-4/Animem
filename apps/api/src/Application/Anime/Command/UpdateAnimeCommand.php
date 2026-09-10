<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Command;

use Yume\Contracts\Bus\CommandInterface;

/**
 * Partial update.
 *
 * `$fields` carries only the keys the caller actually sent, so omitting a field
 * leaves it alone while sending it as null clears it — a distinction a flat set
 * of nullable constructor arguments cannot express.
 */
final readonly class UpdateAnimeCommand implements CommandInterface
{
    /** @param array<string, mixed> $fields */
    public function __construct(
        public string $animeId,
        public array $fields,
        public ?string $actingUserId = null,
    ) {
    }
}
