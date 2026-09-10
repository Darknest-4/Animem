<?php

declare(strict_types=1);

namespace Yume\Tests\Contract;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Enforces the dependency rule of clean architecture: imports point inwards only.
 *
 * Without a test like this, "the domain must not depend on infrastructure" is a
 * README promise that decays on the first deadline. Here it is a failing build.
 *
 *     Presentation -> Application -> Domain <- Infrastructure
 *
 * Domain depends on nothing but itself and the framework-agnostic Contracts.
 */
final class LayerDependencyTest extends TestCase
{
    private const SRC = __DIR__ . '/../../src';

    /**
     * @param list<string> $forbiddenPrefixes
     */
    #[DataProvider('layerRules')]
    public function testLayerDoesNotImportForbiddenNamespaces(string $layer, array $forbiddenPrefixes): void
    {
        $violations = [];

        foreach (self::phpFilesIn(self::SRC . '/' . $layer) as $file) {
            $contents = file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            preg_match_all('/^use\s+([^\s;]+)/m', $contents, $matches);

            foreach ($matches[1] as $import) {
                foreach ($forbiddenPrefixes as $forbidden) {
                    if (str_starts_with($import, $forbidden)) {
                        $violations[] = sprintf(
                            '%s imports %s',
                            str_replace(self::SRC . '/', '', $file),
                            $import,
                        );
                    }
                }
            }
        }

        self::assertSame([], $violations, sprintf(
            "The %s layer must not depend on: %s\n%s",
            $layer,
            implode(', ', $forbiddenPrefixes),
            implode("\n", $violations),
        ));
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function layerRules(): iterable
    {
        yield 'Domain is independent of everything outward' => [
            'Domain',
            [
                'Yume\\Api\\Application',
                'Yume\\Api\\Infrastructure',
                'Yume\\Api\\Presentation',
                'Yume\\Database',      // a concrete PDO connection
                'Yume\\Shared\\Container',
                'PDO',
            ],
        ];

        yield 'Application does not reach into infrastructure or transport' => [
            'Application',
            [
                'Yume\\Api\\Infrastructure',
                'Yume\\Api\\Presentation',
                'Yume\\Database\\Connection',
                'PDO',
            ],
        ];

        yield 'Infrastructure does not depend on the transport layer' => [
            'Infrastructure',
            ['Yume\\Api\\Presentation'],
        ];
    }

    /**
     * The domain also may not reach for global state — superglobals, time or
     * randomness — because that is what makes its rules untestable.
     */
    public function testDomainDoesNotTouchGlobalState(): void
    {
        $forbidden = ['$_GET', '$_POST', '$_SERVER', '$_COOKIE', '$_SESSION', '$_REQUEST', '$_FILES'];
        $violations = [];

        foreach (self::phpFilesIn(self::SRC . '/Domain') as $file) {
            $contents = file_get_contents($file) ?: '';

            foreach ($forbidden as $needle) {
                if (str_contains($contents, $needle)) {
                    $violations[] = str_replace(self::SRC . '/', '', $file) . ' uses ' . $needle;
                }
            }

            // time() and date() smuggle the system clock into business rules;
            // ClockInterface is injected instead.
            if (preg_match('/(?<![\w>$])time\(\)/', $contents) === 1) {
                $violations[] = str_replace(self::SRC . '/', '', $file) . ' calls time() instead of using ClockInterface';
            }
        }

        self::assertSame([], $violations, implode("\n", $violations));
    }

    /** @return list<string> */
    private static function phpFilesIn(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
