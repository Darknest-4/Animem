<?php

declare(strict_types=1);

namespace Yume\Api\Application\Anime\Handler;

use Yume\Api\Application\Anime\Command\CreateAnimeCommand;
use Yume\Api\Application\Anime\DTO\AnimeView;
use Yume\Api\Domain\Anime\Entity\Anime;
use Yume\Api\Domain\Anime\Repository\AnimeRepositoryInterface;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\MediaType;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;

final class CreateAnimeHandler
{
    public function __construct(
        private readonly AnimeRepositoryInterface $anime,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateAnimeCommand $command): AnimeView
    {
        $mediaType = MediaType::tryFrom($command->mediaType);

        if ($mediaType === null) {
            throw new \InvalidArgumentException(sprintf(
                'media_type must be one of: %s.',
                implode(', ', array_column(MediaType::cases(), 'value')),
            ));
        }

        if ($command->malId !== null) {
            $existing = $this->anime->findByMalId($command->malId);

            if ($existing !== null) {
                // Duplicate MAL ids are the most common way this catalogue used
                // to acquire two rows for one show.
                throw new \DomainException(sprintf(
                    'MyAnimeList id %d is already used by "%s".',
                    $command->malId,
                    $existing->title(),
                ));
            }
        }

        $now = $this->clock->now();

        $anime = Anime::draft(
            AnimeId::fromString($this->ids->generate()),
            $this->anime->reserveSlug(Slug::fromTitle($command->title)),
            trim($command->title),
            $mediaType,
            $now,
            $command->actingUserId !== null ? UserId::fromString($command->actingUserId) : null,
        );

        if ($command->malId !== null) {
            $anime->linkToMyAnimeList($command->malId, $now);
        }

        $this->anime->save($anime);

        return AnimeView::fromEntity($anime);
    }
}
