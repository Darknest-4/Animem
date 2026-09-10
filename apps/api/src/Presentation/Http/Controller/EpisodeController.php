<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Episode\Command\AddEpisodeReleaseCommand;
use Yume\Api\Application\Episode\Command\CreateEpisodeCommand;
use Yume\Api\Application\Episode\Command\DeleteEpisodeCommand;
use Yume\Api\Application\Episode\Command\PublishEpisodeCommand;
use Yume\Api\Application\Episode\DTO\EpisodeView;
use Yume\Api\Application\Episode\Query\ListEpisodesQuery;
use Yume\Api\Domain\Authorization\Service\AuthorizationService;
use Yume\Api\Domain\Episode\Entity\EpisodeRelease;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Request\Validator;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Contracts\Bus\CommandBusInterface;
use Yume\Contracts\Bus\QueryBusInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;

final class EpisodeController
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
        $episodes = $this->queries->ask(new ListEpisodesQuery(
            $parameters['id'] ?? '',
            $this->mayEdit($request),
        ));

        return JsonResponse::ok(['episodes' => $episodes]);
    }

    /** @param array<string, string> $parameters */
    public function create(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())
            ->integer('number', 1, 32000)
            ->string('title', 1, 400, required: false)
            ->validated();

        /** @var EpisodeView $episode */
        $episode = $this->commands->dispatch(new CreateEpisodeCommand(
            $parameters['id'] ?? '',
            (int) $data['number'],
            isset($data['title']) ? (string) $data['title'] : null,
            self::actingUserId($request),
        ));

        return JsonResponse::created(['episode' => $episode->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function addRelease(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())
            ->string('uploader_id', 36, 36)
            ->string('url', 8, 1024)
            ->string('kind', 3, 8, required: false)
            ->string('language', 2, 5, required: false)
            ->validated();

        /** @var EpisodeView $episode */
        $episode = $this->commands->dispatch(new AddEpisodeReleaseCommand(
            $parameters['id'] ?? '',
            (string) $data['uploader_id'],
            (string) $data['url'],
            isset($data['kind']) ? (string) $data['kind'] : 'sub',
            isset($data['language']) ? (string) $data['language'] : 'hu',
            self::actingUserId($request),
        ));

        return JsonResponse::created(['episode' => $episode->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function publish(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())->boolean('published')->validated();

        /** @var EpisodeView $episode */
        $episode = $this->commands->dispatch(new PublishEpisodeCommand(
            $parameters['id'] ?? '',
            (bool) $data['published'],
            self::actingUserId($request),
        ));

        return JsonResponse::ok(['episode' => $episode->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function delete(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $this->commands->dispatch(new DeleteEpisodeCommand(
            $parameters['id'] ?? '',
            $request->ip(),
            $request->userAgent(),
            self::actingUserId($request),
        ));

        return JsonResponse::ok(['deleted' => true]);
    }

    /**
     * The hosts a release URL may point at.
     *
     * Public so the upload form can validate before submitting rather than
     * discovering the rule from a 422.
     *
     * @param array<string, string> $parameters
     */
    public function allowedHosts(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        return JsonResponse::ok(['allowed_hosts' => EpisodeRelease::allowedHosts()]);
    }

    private function mayEdit(RequestInterface $request): bool
    {
        $raw = $request->attribute('user_id');

        return $this->authorization->allows(
            is_string($raw) ? UserId::fromString($raw) : null,
            'episode.edit',
        );
    }

    private static function actingUserId(RequestInterface $request): ?string
    {
        $raw = $request->attribute('user_id');

        return is_string($raw) ? $raw : null;
    }
}
