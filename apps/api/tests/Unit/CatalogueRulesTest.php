<?php

declare(strict_types=1);

namespace Yume\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yume\Api\Domain\Anime\Entity\Anime;
use Yume\Api\Domain\Anime\ValueObject\AnimeId;
use Yume\Api\Domain\Anime\ValueObject\MediaType;
use Yume\Api\Domain\Anime\ValueObject\Season;
use Yume\Api\Domain\Anime\ValueObject\Slug;
use Yume\Api\Domain\Community\ValueObject\UploaderId;
use Yume\Api\Domain\Episode\Entity\Episode;
use Yume\Api\Domain\Episode\Entity\EpisodeRelease;
use Yume\Api\Domain\Episode\ValueObject\EpisodeId;
use Yume\Api\Domain\Episode\ValueObject\ReleaseKind;

final class CatalogueRulesTest extends TestCase
{
    private const ANIME_ID = '01a08900-5a13-771f-8428-3e352b4efc4d';
    private const EPISODE_ID = '01a08900-5ac5-7377-aebd-dbbf300ff1b5';
    private const UPLOADER_ID = '01a08913-b125-770f-b0c6-355c7ea23692';

    // ---------------------------------------------------------------- slugs

    #[DataProvider('titlesAndSlugs')]
    public function testSlugsAreDerivedFromTitles(string $title, string $expected): void
    {
        self::assertSame($expected, Slug::fromTitle($title)->value);
    }

    /** @return iterable<string, array{string, string}> */
    public static function titlesAndSlugs(): iterable
    {
        yield 'plain' => ['Cowboy Bebop', 'cowboy-bebop'];
        yield 'punctuation' => ['Re:Zero — Starting Life', 're-zero-starting-life'];
        yield 'accents' => ['Ámokfutó Álom', 'amokfuto-alom'];
        yield 'collapses runs' => ['A   B', 'a-b'];
    }

    /** A title with no Latin characters still has to produce a usable identifier. */
    public function testANonLatinTitleStillYieldsAStableSlug(): void
    {
        $first = Slug::fromTitle('カウボーイビバップ');
        $second = Slug::fromTitle('カウボーイビバップ');

        self::assertSame($first->value, $second->value, 'the fallback must be deterministic');
        self::assertMatchesRegularExpression('/^[a-z0-9-]+$/', $first->value);
    }

    public function testSlugSuffixStaysWithinTheColumnLimit(): void
    {
        $long = Slug::fromTitle(str_repeat('a', Slug::MAX_LENGTH + 50));

        self::assertLessThanOrEqual(Slug::MAX_LENGTH, mb_strlen($long->withSuffix(12)->value));
    }

    // ---------------------------------------------------------------- anime

    public function testANewEntryIsADraft(): void
    {
        self::assertFalse($this->anime()->isPublished());
    }

    public function testPublishingIsRefusedUntilTheEntryIsReadable(): void
    {
        $anime = $this->anime();

        self::assertSame(['synopsis', 'cover_url'], $anime->missingForPublication());
        self::assertFalse($anime->canBePublished());

        $this->expectException(\DomainException::class);
        $anime->publish(new \DateTimeImmutable());
    }

    public function testPublishingSucceedsOnceTheGapsAreFilled(): void
    {
        $now = new \DateTimeImmutable();
        $anime = $this->anime();

        $anime->describe('Fejvadászok a Naprendszerben.', $now);
        $anime->setCoverUrl('https://cdn.yume.local/bebop.jpg', $now);

        self::assertTrue($anime->canBePublished());
        $anime->publish($now);
        self::assertTrue($anime->isPublished());
    }

    /**
     * Season is derived from the first air date rather than stored separately,
     * so the two can never disagree.
     */
    public function testSeasonIsDerivedFromTheAiringWindow(): void
    {
        $anime = $this->anime();
        $anime->setAiringWindow(new \DateTimeImmutable('1998-04-03'), new \DateTimeImmutable('1999-04-24'), new \DateTimeImmutable());

        self::assertSame(Season::Spring, $anime->season());
        self::assertSame(1998, $anime->seasonYear());
    }

    public function testAnAiringWindowCannotEndBeforeItStarts(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->anime()->setAiringWindow(
            new \DateTimeImmutable('1999-01-01'),
            new \DateTimeImmutable('1998-01-01'),
            new \DateTimeImmutable(),
        );
    }

    #[DataProvider('outOfRangeScores')]
    public function testScoreMustBeWithinRange(float $score): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->anime()->setScore($score, new \DateTimeImmutable());
    }

    /** @return iterable<string, array{float}> */
    public static function outOfRangeScores(): iterable
    {
        yield 'negative' => [-0.1];
        yield 'above ten' => [10.1];
    }

    public function testGenresAreDeduplicatedAndLowercased(): void
    {
        $anime = $this->anime();
        $anime->setGenres(['Action', 'action', 'SPACE'], new \DateTimeImmutable());

        self::assertSame(['action', 'space'], $anime->genreSlugs());
    }

    // -------------------------------------------------------------- episodes

    public function testAnEpisodeCannotBePublishedWithNothingToWatch(): void
    {
        $this->expectException(\DomainException::class);

        $this->episode()->publish(0, new \DateTimeImmutable());
    }

    public function testAnEpisodePublishesOnceItHasARelease(): void
    {
        $episode = $this->episode();
        $episode->publish(1, new \DateTimeImmutable());

        self::assertTrue($episode->isPublished());
    }

    public function testEpisodeNumbersStartAtOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Episode::create(
            EpisodeId::fromString(self::EPISODE_ID),
            AnimeId::fromString(self::ANIME_ID),
            0,
            new \DateTimeImmutable(),
        );
    }

    // -------------------------------------------------------------- releases

    /**
     * The legacy site embedded whatever string sat in its `links` table, which
     * makes the video page only as safe as its least careful uploader.
     */
    #[DataProvider('rejectedReleaseUrls')]
    public function testReleaseUrlsOutsideTheAllowlistAreRejected(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->release($url);
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedReleaseUrls(): iterable
    {
        yield 'unknown host' => ['https://evil.example.com/video'];
        yield 'plain http' => ['http://indavideo.hu/video/abc'];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'data uri' => ['data:text/html;base64,PHNjcmlwdD4='];
        yield 'no scheme' => ['indavideo.hu/video/abc'];
        yield 'lookalike host' => ['https://indavideo.hu.evil.example.com/x'];
    }

    public function testAnAllowlistedHttpsUrlIsAccepted(): void
    {
        $release = $this->release('https://embed.indavideo.hu/player/video/abc123');

        self::assertSame('embed.indavideo.hu', $release->host);
        self::assertSame('hu', $release->language);
    }

    public function testLanguageMustBeAnIsoCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->release('https://youtu.be/abc', 'magyarul');
    }

    private function anime(): Anime
    {
        return Anime::draft(
            AnimeId::fromString(self::ANIME_ID),
            Slug::fromString('cowboy-bebop'),
            'Cowboy Bebop',
            MediaType::Tv,
            new \DateTimeImmutable(),
        );
    }

    private function episode(): Episode
    {
        return Episode::create(
            EpisodeId::fromString(self::EPISODE_ID),
            AnimeId::fromString(self::ANIME_ID),
            1,
            new \DateTimeImmutable(),
        );
    }

    private function release(string $url, string $language = 'hu'): EpisodeRelease
    {
        return EpisodeRelease::create(
            'release-1',
            EpisodeId::fromString(self::EPISODE_ID),
            UploaderId::fromString(self::UPLOADER_ID),
            $url,
            ReleaseKind::Sub,
            $language,
            new \DateTimeImmutable(),
        );
    }
}
