<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Handler;

use Yume\Api\Application\Anime\Command\PublishAnimeCommand;
use Yume\Api\Application\Anime\DTO\AnimeView;
use Yume\Api\Domain\Anime\Exception\AnimeNotFoundException;
use Yume\Api\Domain\Anime\Exception\AnimeNotPublishableException;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Contracts\Clock\ClockInterface;

final class PublishAnimeHandler
{
    public function __construct(
        private readonly AnimeRepositoryInterface $anime,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(PublishAnimeCommand $command): AnimeView
    {
        $anime = $this->anime->findById(AnimeId::fromString($command->animeId));

        if ($anime === null) {
            throw AnimeNotFoundException::withId($command->animeId);
        }

        $now = $this->clock->now();

        if (!$command->published) {
            $anime->unpublish($now);
            $this->anime->save($anime);

            return AnimeView::fromEntity($anime);
        }

        // Ask the entity what is missing rather than publishing and catching:
        // the API error can then name the fields an editor still has to fill in.
        $missing = $anime->missingForPublication();

        if ($missing !== []) {
            throw new AnimeNotPublishableException($missing);
        }

        $anime->publish($now);

        $this->anime->save($anime);

        return AnimeView::fromEntity($anime);
    }
}
