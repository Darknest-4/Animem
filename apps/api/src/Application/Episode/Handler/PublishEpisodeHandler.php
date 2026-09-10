<?php

declare(strict_types=1);

namespace Yume\Api\Application\Episode\Handler;

use Yume\Api\Application\Episode\Command\PublishEpisodeCommand;
use Yume\Api\Application\Episode\DTO\EpisodeView;
use Yume\Api\Domain\Episode\Repository\EpisodeRepositoryInterface;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;
use Yume\Contracts\Clock\ClockInterface;

final class PublishEpisodeHandler
{
    public function __construct(
        private readonly EpisodeRepositoryInterface $episodes,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(PublishEpisodeCommand $command): EpisodeView
    {
        $episodeId = EpisodeId::fromString($command->episodeId);
        $episode = $this->episodes->findById($episodeId);

        if ($episode === null) {
            throw new \DomainException('No such episode.');
        }

        $now = $this->clock->now();

        if ($command->published) {
            // The entity refuses to publish an episode with nothing to watch.
            $episode->publish($this->episodes->countReleases($episodeId), $now);
        } else {
            $episode->unpublish($now);
        }

        $this->episodes->save($episode);

        return EpisodeView::fromEntity($episode, $this->episodes->releasesFor($episodeId));
    }
}
