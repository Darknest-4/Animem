<?php

declare(strict_types=1);

namespace Yume\Api\Application\Community\Command;

use Yume\Contracts\Bus\CommandInterface;

/**
 * Creates or updates a fansub group.
 *
 * One command for both because the fields and the validation are identical;
 * `$uploaderId` being null is the only difference, and splitting it would
 * duplicate every rule.
 */
final readonly class SaveUploaderCommand implements CommandInterface
{
    /** @param array<string, mixed> $fields */
    public function __construct(
        public ?string $uploaderId,
        public array $fields,
        public ?string $actingUserId = null,
    ) {
    }
}
