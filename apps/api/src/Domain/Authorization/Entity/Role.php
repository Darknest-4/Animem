<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Authorization\Entity;

use Yume\Api\Domain\Authorization\ValueObject\PermissionSet;

final readonly class Role
{
    public function __construct(
        public string $slug,
        public string $name,
        public PermissionSet $permissions,
        /** System roles cannot be deleted or renamed through the admin API. */
        public bool $isSystem = false,
    ) {
    }
}
