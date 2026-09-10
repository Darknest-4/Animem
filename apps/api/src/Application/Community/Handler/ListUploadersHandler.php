<?php

declare(strict_types=1);

namespace Yume\Api\Application\Community\Handler;

use Yume\Api\Application\Community\DTO\UploaderView;
use Yume\Api\Application\Community\Query\ListUploadersQuery;
use Yume\Api\Domain\Community\Entity\Uploader;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;

final class ListUploadersHandler
{
    public function __construct(private readonly UploaderRepositoryInterface $uploaders)
    {
    }

    /** @return list<array<string, mixed>> */
    public function __invoke(ListUploadersQuery $query): array
    {
        return array_map(
            static fn (Uploader $uploader): array => UploaderView::fromEntity($uploader)->toArray(),
            $this->uploaders->all($query->includeInactive),
        );
    }
}
