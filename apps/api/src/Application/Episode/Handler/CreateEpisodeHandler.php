<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Handler;

use Yume\Api\Application\Episode\Command\CreateEpisodeCommand;
use Yume\Api\Application\Episode\DTO\EpisodeView;
use Yume\Api\Domain\Anime\Exception\AnimeNotFoundException;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Episode\Entity\Episode;
use Yume\Api\Domain\Episode\Repository\EpisodeRepositoryInterface;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;

final class CreateEpisodeHandler
{
    public function __construct(
        private readonly EpisodeRepositoryInterface $episodes,
        private readonly AnimeRepositoryInterface $anime,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateEpisodeCommand $command): EpisodeView
    {
        $animeId = AnimeId::fromString($command->animeId);

        if ($this->anime->findById($animeId) === null) {
            throw AnimeNotFoundException::withId($command->animeId);
        }

        // Checked for a readable error; the unique index on (anime_id, number)
        // is what holds under two editors adding episode 7 at once.
        if ($this->episodes->findByNumber($animeId, $command->number) !== null) {
            throw new \DomainException(sprintf('Episode %d already exists for this title.', $command->number));
        }

        $episode = Episode::create(
            EpisodeId::fromString($this->ids->generate()),
            $animeId,
            $command->number,
            $this->clock->now(),
            $command->title,
            $command->actingUserId !== null ? UserId::fromString($command->actingUserId) : null,
        );

        $this->episodes->save($episode);

        return EpisodeView::fromEntity($episode);
    }
}
