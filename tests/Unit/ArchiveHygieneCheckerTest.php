<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Tests\TestCase;
use Zarbin\IranLocations\Tools\ArchiveHygieneChecker;
use ZipArchive;

require_once dirname(__DIR__, 2).'/tools/check-archive.php';

class ArchiveHygieneCheckerTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $temporaryPaths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->temporaryPaths) as $path) {
            $this->removePath($path);
        }

        parent::tearDown();
    }

    public function test_safe_archive_and_project_export_ignores_pass(): void
    {
        $zipPath = $this->zipWithEntries([
            'README.md' => '# Package',
            'src/Foo.php' => '<?php',
            'docs/api.md' => '# API',
        ]);

        $violations = (new ArchiveHygieneChecker)->check(
            $zipPath,
            dirname(__DIR__, 2),
        );

        self::assertSame([], $violations);
    }

    public function test_forbidden_archive_entries_are_reported(): void
    {
        $zipPath = $this->zipWithEntries([
            '_review/PHASE_7_REVIEW_NOTES.md' => 'private',
            'vendor/autoload.php' => '<?php',
            'nested/patch.zip' => 'zip',
        ]);

        $violations = (new ArchiveHygieneChecker)->checkArchive($zipPath);

        self::assertNotSame([], $violations);
        self::assertStringContainsString('_review/PHASE_7_REVIEW_NOTES.md', implode("\n", $violations));
        self::assertStringContainsString('vendor/autoload.php', implode("\n", $violations));
        self::assertStringContainsString('nested/patch.zip', implode("\n", $violations));
    }

    public function test_missing_export_ignore_rules_are_reported(): void
    {
        $root = $this->temporaryDirectory();
        file_put_contents($root.'/.gitattributes', "/*.zip export-ignore\n");

        $violations = (new ArchiveHygieneChecker)->checkGitattributes($root);

        self::assertContains('Missing .gitattributes export-ignore rule: /_review export-ignore', $violations);
        self::assertContains('Missing .gitattributes export-ignore rule: /vendor export-ignore', $violations);
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function zipWithEntries(array $entries): string
    {
        $path = $this->temporaryFile('.zip');
        $archive = new ZipArchive;

        self::assertTrue($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $name => $contents) {
            self::assertTrue($archive->addFromString($name, $contents));
        }

        self::assertTrue($archive->close());

        return $path;
    }

    private function temporaryFile(string $suffix = ''): string
    {
        $path = tempnam(sys_get_temp_dir(), 'iran-locations-archive-');
        self::assertIsString($path);

        if ($suffix !== '') {
            $suffixed = $path.$suffix;
            rename($path, $suffixed);
            $path = $suffixed;
        }

        $this->temporaryPaths[] = $path;

        return $path;
    }

    private function temporaryDirectory(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'iran-locations-archive-'.bin2hex(random_bytes(6));
        mkdir($path);
        $this->temporaryPaths[] = $path;

        return $path;
    }

    private function removePath(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            unlink($path);

            return;
        }

        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $this->removePath($path.DIRECTORY_SEPARATOR.$item);
        }

        rmdir($path);
    }
}
