<?php

declare(strict_types=1);

namespace Yume\Api\Application\Community\Handler;

use Yume\Api\Application\Community\Command\SaveUploaderCommand;
use Yume\Api\Application\Community\DTO\UploaderView;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\Community\Entity\Uploader;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;
use Yume\Api\Domain\Community\ValueObject\UploaderId;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;

final class SaveUploaderHandler
{
    public function __construct(
        private readonly UploaderRepositoryInterface $uploaders,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(SaveUploaderCommand $command): UploaderView
    {
        $now = $this->clock->now();
        $fields = $command->fields;
        $has = static fn (string $key): bool => array_key_exists($key, $fields);

        if ($command->uploaderId === null) {
            $name = trim((string) ($fields['name'] ?? ''));

            if ($name === '') {
                throw new \InvalidArgumentException('A fansub group needs a name.');
            }

            $slug = $has('slug')
                ? Slug::fromString((string) $fields['slug'])
                : Slug::fromTitle($name);

            if ($this->uploaders->slugExists($slug)) {
                throw new \DomainException(sprintf('A group with the slug "%s" already exists.', $slug->value));
            }

            $uploader = Uploader::create(
                UploaderId::fromString($this->ids->generate()),
                $slug,
                $name,
                $now,
            );
        } else {
            $uploaderId = UploaderId::fromString($command->uploaderId);
            $uploader = $this->uploaders->findById($uploaderId);

            if ($uploader === null) {
                throw new \DomainException('No such fansub group.');
            }

            if ($has('name')) {
                $uploader->rename((string) $fields['name'], $now);
            }
        }

        if ($has('description')) {
            $uploader->describe(self::asNullableString($fields['description']), $now);
        }

        if ($has('website_url') || $has('facebook_url') || $has('video_url')) {
            $uploader->setLinks(
                $has('website_url') ? self::asNullableString($fields['website_url']) : $uploader->websiteUrl(),
                $has('facebook_url') ? self::asNullableString($fields['facebook_url']) : $uploader->facebookUrl(),
                $has('video_url') ? self::asNullableString($fields['video_url']) : $uploader->videoUrl(),
                $now,
            );
        }

        if ($has('email')) {
            $uploader->setEmail(self::asNullableString($fields['email']), $now);
        }

        if ($has('is_active')) {
            filter_var($fields['is_active'], FILTER_VALIDATE_BOOL)
                ? $uploader->reactivate($now)
                : $uploader->deactivate($now);
        }

        $this->uploaders->save($uploader);

        return UploaderView::fromEntity($uploader);
    }

    private static function asNullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
