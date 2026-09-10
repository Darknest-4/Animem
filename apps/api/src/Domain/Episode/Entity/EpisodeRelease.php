<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Episode\Entity;

use Yume\Api\Domain\Community\ValueObject\UploaderId;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;
use Yume\Api\Domain\Episode\ValueObject\ReleaseKind;
use Yume\Api\Domain\User\ValueObject\UserId;

/**
 * One fansub group's upload of one episode.
 *
 * The URL is validated here rather than at the edge because "which hosts are we
 * willing to embed" is a policy question, not an input-format question — and
 * embedding an arbitrary attacker-supplied URL is how a video page becomes an
 * XSS vector.
 */
final class EpisodeRelease
{
    private const ALLOWED_HOSTS = [
        'indavideo.hu', 'embed.indavideo.hu', 'youtube.com', 'www.youtube.com',
        'youtu.be', 'dailymotion.com', 'www.dailymotion.com', 'vimeo.com', 'player.vimeo.com',
    ];

    private function __construct(
        public readonly string $id,
        public readonly EpisodeId $episodeId,
        public readonly UploaderId $uploaderId,
        public readonly string $language,
        public readonly ReleaseKind $kind,
        public readonly string $host,
        public readonly string $url,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?UserId $createdBy,
    ) {
    }

    public static function create(
        string $id,
        EpisodeId $episodeId,
        UploaderId $uploaderId,
        string $url,
        ReleaseKind $kind,
        string $language,
        \DateTimeImmutable $now,
        ?UserId $createdBy = null,
    ): self {
        $host = self::assertEmbeddableUrl($url);

        if (preg_match('/^[a-z]{2}(?:-[a-z]{2})?$/i', $language) !== 1) {
            throw new \InvalidArgumentException('Language must be an ISO 639-1 code, optionally with a region.');
        }

        return new self($id, $episodeId, $uploaderId, strtolower($language), $kind, $host, $url, $now, $createdBy);
    }

    public static function reconstitute(
        string $id,
        EpisodeId $episodeId,
        UploaderId $uploaderId,
        string $language,
        ReleaseKind $kind,
        string $host,
        string $url,
        \DateTimeImmutable $createdAt,
        ?UserId $createdBy,
    ): self {
        return new self($id, $episodeId, $uploaderId, $language, $kind, $host, $url, $createdAt, $createdBy);
    }

    /** @return string the validated host @throws \InvalidArgumentException */
    private static function assertEmbeddableUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new \InvalidArgumentException('The release URL is not a valid absolute URL.');
        }

        // http:// on an embedded player produces mixed-content warnings and is
        // trivially interceptable.
        if (strtolower($parts['scheme']) !== 'https') {
            throw new \InvalidArgumentException('The release URL must use https.');
        }

        $host = strtolower($parts['host']);

        if (!in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Host "%s" is not on the embeddable allowlist (%s).',
                $host,
                implode(', ', self::ALLOWED_HOSTS),
            ));
        }

        return $host;
    }

    /** @return list<string> */
    public static function allowedHosts(): array
    {
        return self::ALLOWED_HOSTS;
    }
}
