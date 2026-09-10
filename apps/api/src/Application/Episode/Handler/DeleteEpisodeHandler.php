<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Handler;

use Yume\Api\Application\Episode\Command\DeleteEpisodeCommand;
use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Episode\Repository\EpisodeRepositoryInterface;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;

/** Deleting an episode discards every group's release of it, so it is audited. */
final class DeleteEpisodeHandler
{
    public function __construct(
        private readonly EpisodeRepositoryInterface $episodes,
        private readonly SecurityAuditor $auditor,
    ) {
    }

    public function __invoke(DeleteEpisodeCommand $command): bool
    {
        $episodeId = EpisodeId::fromString($command->episodeId);
        $episode = $this->episodes->findById($episodeId);

        if ($episode === null) {
            throw new \DomainException('No such episode.');
        }

        $releaseCount = $this->episodes->countReleases($episodeId);
        $this->episodes->delete($episodeId);

        $this->auditor->record(
            SecurityEventType::CatalogueDeleted,
            IpAddress::fromString($command->ip),
            UserAgent::fromString($command->userAgent),
            'DELETE',
            '/api/v1/episodes/' . $episodeId->value,
            $command->actingUserId !== null ? UserId::fromString($command->actingUserId) : null,
            0,
            [
                'entity' => 'episode',
                'anime_id' => $episode->animeId->value,
                'number' => $episode->number(),
                'releases_discarded' => $releaseCount,
            ],
        );

        return true;
    }
}
