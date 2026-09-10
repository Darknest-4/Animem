<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Community\Command\SaveUploaderCommand;
use Yume\Api\Application\Community\DTO\UploaderView;
use Yume\Api\Application\Community\Query\ListUploadersQuery;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\Authorization\Service\AuthorizationService;
use Yume\Api\Domain\Community\Repository\UploaderRepositoryInterface;
use Yume\Api\Domain\Community\ValueObject\UploaderId;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Bus\CommandBusInterface;
use Yume\Contracts\Bus\QueryBusInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

final class UploaderController
{
    public function __construct(
        private readonly CommandBusInterface $commands,
        private readonly QueryBusInterface $queries,
        private readonly UploaderRepositoryInterface $uploaders,
        private readonly AuthorizationService $authorization,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function index(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $raw = $request->attribute('user_id');
        $mayManage = $this->authorization->allows(
            is_string($raw) ? UserId::fromString($raw) : null,
            'uploader.manage',
        );

        return JsonResponse::ok([
            'uploaders' => $this->queries->ask(new ListUploadersQuery($mayManage)),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function show(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $identifier = $parameters['identifier'] ?? '';
        $uploader = null;

        try {
            $uploader = $this->uploaders->findById(UploaderId::fromString($identifier));
        } catch (\InvalidArgumentException) {
            try {
                $uploader = $this->uploaders->findBySlug(Slug::fromString($identifier));
            } catch (\InvalidArgumentException) {
                $uploader = null;
            }
        }

        if ($uploader === null) {
            return ProblemDetails::make(404, 'uploader.not_found', 'No such fansub group.');
        }

        return JsonResponse::ok(['uploader' => UploaderView::fromEntity($uploader)->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function create(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        /** @var UploaderView $uploader */
        $uploader = $this->commands->dispatch(new SaveUploaderCommand(
            null,
            $request->body(),
            self::actingUserId($request),
        ));

        return JsonResponse::created(['uploader' => $uploader->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function update(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        /** @var UploaderView $uploader */
        $uploader = $this->commands->dispatch(new SaveUploaderCommand(
            $parameters['id'] ?? '',
            $request->body(),
            self::actingUserId($request),
        ));

        return JsonResponse::ok(['uploader' => $uploader->toArray()]);
    }

    private static function actingUserId(RequestInterface $request): ?string
    {
        $raw = $request->attribute('user_id');

        return is_string($raw) ? $raw : null;
    }
}
