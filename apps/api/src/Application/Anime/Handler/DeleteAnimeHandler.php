<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Handler;

use Yume\Api\Application\Anime\Command\DeleteAnimeCommand;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Anime\Exception\AnimeNotFoundException;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;

/**
 * Deletion cascades to episodes and releases, so it is audited: recovering the
 * "who deleted the whole season" answer from a backup is much harder than
 * writing one row now.
 */
final class DeleteAnimeHandler
{
    public function __construct(
        private readonly AnimeRepositoryInterface $anime,
        private readonly SecurityAuditor $auditor,
    ) {
    }

    public function __invoke(DeleteAnimeCommand $command): bool
    {
        $animeId = AnimeId::fromString($command->animeId);
        $anime = $this->anime->findById($animeId);

        if ($anime === null) {
            throw AnimeNotFoundException::withId($command->animeId);
        }

        $this->anime->delete($animeId);

        $this->auditor->record(
            SecurityEventType::CatalogueDeleted,
            IpAddress::fromString($command->ip),
            UserAgent::fromString($command->userAgent),
            'DELETE',
            '/api/v1/anime/' . $animeId->value,
            $command->actingUserId !== null ? UserId::fromString($command->actingUserId) : null,
            0,
            ['title' => $anime->title(), 'slug' => $anime->slug()->value],
        );

        return true;
    }
}
