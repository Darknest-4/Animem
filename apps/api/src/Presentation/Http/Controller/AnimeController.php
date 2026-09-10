<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Anime\Command\CreateAnimeCommand;
use Yume\Api\Application\Anime\Command\DeleteAnimeCommand;
use Yume\Api\Application\Anime\Command\PublishAnimeCommand;
use Yume\Api\Application\Anime\Command\UpdateAnimeCommand;
use Yume\Api\Application\Anime\DTO\AnimeView;
use Yume\Api\Application\Anime\Query\GetAnimeQuery;
use Yume\Api\Application\Anime\Query\SearchAnimeQuery;
use Yume\Api\Domain\Authorization\Service\AuthorizationService;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Request\Validator;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Contracts\Bus\CommandBusInterface;
use Yume\Contracts\Bus\QueryBusInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

/**
 * The catalogue.
 *
 * The one judgement call this controller makes is whether the caller may see
 * drafts. It asks AuthorizationService rather than deciding, and passes the
 * answer into the query — the handler never infers permissions for itself.
 */
final class AnimeController
{
    public function __construct(
        private readonly CommandBusInterface $commands,
        private readonly QueryBusInterface $queries,
        private readonly AuthorizationService $authorization,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function index(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $result = $this->queries->ask(new SearchAnimeQuery(
            self::queryString($request, 'q'),
            self::queryString($request, 'genre'),
            self::queryString($request, 'type'),
            self::queryString($request, 'status'),
            self::queryString($request, 'season'),
            self::queryInt($request, 'year'),
            self::queryString($request, 'sort') ?? 'recent',
            self::queryInt($request, 'page') ?? 1,
            self::queryInt($request, 'per_page') ?? 24,
            $this->mayEditCatalogue($request),
        ));

        return JsonResponse::ok($result);
    }

    /** @param array<string, string> $parameters */
    public function show(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        /** @var AnimeView $anime */
        $anime = $this->queries->ask(new GetAnimeQuery(
            $parameters['identifier'] ?? '',
            $this->mayEditCatalogue($request),
        ));

        return JsonResponse::ok(['anime' => $anime->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function create(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())
            ->string('title', 1, 400)
            ->string('media_type', 2, 16)
            ->integer('mal_id', 1, 2000000, required: false)
            ->validated();

        /** @var AnimeView $anime */
        $anime = $this->commands->dispatch(new CreateAnimeCommand(
            (string) $data['title'],
            (string) $data['media_type'],
            self::actingUserId($request),
            isset($data['mal_id']) ? (int) $data['mal_id'] : null,
        ));

        return JsonResponse::created(['anime' => $anime->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function update(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        /** @var AnimeView $anime */
        $anime = $this->commands->dispatch(new UpdateAnimeCommand(
            $parameters['id'] ?? '',
            $request->body(),
            self::actingUserId($request),
        ));

        return JsonResponse::ok(['anime' => $anime->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function publish(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())->boolean('published')->validated();

        /** @var AnimeView $anime */
        $anime = $this->commands->dispatch(new PublishAnimeCommand(
            $parameters['id'] ?? '',
            (bool) $data['published'],
            self::actingUserId($request),
        ));

        return JsonResponse::ok(['anime' => $anime->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function delete(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $this->commands->dispatch(new DeleteAnimeCommand(
            $parameters['id'] ?? '',
            $request->ip(),
            $request->userAgent(),
            self::actingUserId($request),
        ));

        return JsonResponse::ok(['deleted' => true]);
    }

    private function mayEditCatalogue(RequestInterface $request): bool
    {
        $raw = $request->attribute('user_id');

        return $this->authorization->allows(
            is_string($raw) ? UserId::fromString($raw) : null,
            'anime.edit',
        );
    }

    private static function actingUserId(RequestInterface $request): ?string
    {
        $raw = $request->attribute('user_id');

        return is_string($raw) ? $raw : null;
    }

    private static function queryString(RequestInterface $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function queryInt(RequestInterface $request, string $key): ?int
    {
        $value = $request->query($key);

        return is_numeric($value) ? (int) $value : null;
    }
}
