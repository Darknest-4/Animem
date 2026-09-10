<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Handler;

use Yume\Api\Application\Episode\Command\AddEpisodeReleaseCommand;
use Yume\Api\Application\Episode\DTO\EpisodeView;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;
use Yume\Api\Domain\Community\ValueObject\UploaderId;
use Yume\Api\Domain\Episode\Entity\EpisodeRelease;
use Yume\Api\Domain\Episode\Repository\EpisodeRepositoryInterface;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;
use Yume\Api\Domain\Episode\ValueObject\ReleaseKind;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;

/**
 * Attaches a fansub group's upload to an episode.
 *
 * The URL is validated by EpisodeRelease against an embeddable-host allowlist:
 * the legacy site embedded whatever string was in the `links` table, which makes
 * the video page only as safe as its least careful uploader.
 */
final class AddEpisodeReleaseHandler
{
    public function __construct(
        private readonly EpisodeRepositoryInterface $episodes,
        private readonly UploaderRepositoryInterface $uploaders,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(AddEpisodeReleaseCommand $command): EpisodeView
    {
        $episodeId = EpisodeId::fromString($command->episodeId);
        $episode = $this->episodes->findById($episodeId);

        if ($episode === null) {
            throw new \DomainException('No such episode.');
        }

        $uploaderId = UploaderId::fromString($command->uploaderId);

        if ($this->uploaders->findById($uploaderId) === null) {
            throw new \DomainException('No such fansub group.');
        }

        $kind = ReleaseKind::tryFrom($command->kind);

        if ($kind === null) {
            throw new \InvalidArgumentException('kind must be sub, dub or raw.');
        }

        $this->episodes->saveRelease(EpisodeRelease::create(
            $this->ids->generate(),
            $episodeId,
            $uploaderId,
            $command->url,
            $kind,
            $command->language,
            $this->clock->now(),
            $command->actingUserId !== null ? UserId::fromString($command->actingUserId) : null,
        ));

        $releases = $this->episodes->releasesFor($episodeId);

        $names = [];
        foreach ($releases as $release) {
            $uploader = $this->uploaders->findById($release->uploaderId);
            if ($uploader !== null) {
                $names[$release->uploaderId->value] = $uploader->name();
            }
        }

        return EpisodeView::fromEntity($episode, $releases, $names);
    }
}
