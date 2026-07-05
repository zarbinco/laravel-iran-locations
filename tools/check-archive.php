#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tools;

use ZipArchive;

final class ArchiveHygieneChecker
{
    /**
     * @var array<string, string>
     */
    private const FORBIDDEN_PATTERNS = [
        'root review notes' => '#(^|/)REVIEW_NOTES\.md$#',
        'phpunit cache directory' => '#(^|/)\.phpunit\.cache(/|$)#',
        'phpunit result cache' => '#(^|/)\.phpunit\.result\.cache$#',
        'review directory' => '#(^|/)_review(/|$)#',
        'vendor directory' => '#(^|/)vendor(/|$)#',
        'node_modules directory' => '#(^|/)node_modules(/|$)#',
        'coverage directory' => '#(^|/)coverage(/|$)#',
        'artifacts directory' => '#(^|/)artifacts(/|$)#',
        'source staging directory' => '#(^|/)_source(/|$)#',
        'git directory' => '#(^|/)\.git(/|$)#',
        'build directory' => '#(^|/)build(/|$)#',
        'release directory' => '#(^|/)release(/|$)#',
        'dist directory' => '#(^|/)dist(/|$)#',
    ];

    /**
     * @var array<int, string>
     */
    private const REQUIRED_EXPORT_IGNORES = [
        '/.git export-ignore',
        '/.github export-ignore',
        '/.gitattributes export-ignore',
        '/.gitignore export-ignore',
        '/.phpunit.cache export-ignore',
        '/.phpunit.result.cache export-ignore',
        '/.php-cs-fixer.cache export-ignore',
        '/.phpstan.cache export-ignore',
        '/coverage export-ignore',
        '/build export-ignore',
        '/dist export-ignore',
        '/release export-ignore',
        '/artifacts export-ignore',
        '/vendor export-ignore',
        '/node_modules export-ignore',
        '/_review export-ignore',
        '/_source export-ignore',
        '/REVIEW_NOTES.md export-ignore',
        '/*.zip export-ignore',
        '/*.tar export-ignore',
        '/*.tar.gz export-ignore',
    ];

    /**
     * @return array<int, string>
     */
    public function check(string $zipPath, string $projectRoot): array
    {
        return [
            ...$this->checkArchive($zipPath),
            ...$this->checkGitattributes($projectRoot),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function checkArchive(string $zipPath): array
    {
        if (! class_exists(ZipArchive::class)) {
            return ['PHP zip extension is required for archive hygiene checks. Install/enable ext-zip.'];
        }

        if (! is_file($zipPath)) {
            return ["Archive does not exist: {$zipPath}"];
        }

        $archive = new ZipArchive;
        $opened = $archive->open($zipPath);

        if ($opened !== true) {
            return ["Archive could not be opened: {$zipPath}"];
        }

        $violations = [];

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $entry = $archive->getNameIndex($index);

            if (! is_string($entry)) {
                continue;
            }

            $entry = ltrim(str_replace('\\', '/', $entry), './');

            foreach (self::FORBIDDEN_PATTERNS as $label => $pattern) {
                if (preg_match($pattern, $entry) === 1) {
                    $violations[] = "Forbidden {$label} found in archive: {$entry}";
                }
            }

            if (preg_match('#\.(zip|tar|tar\.gz|tgz)$#i', $entry) === 1) {
                $violations[] = "Nested archive found in archive: {$entry}";
            }
        }

        $archive->close();

        return array_values(array_unique($violations));
    }

    /**
     * @return array<int, string>
     */
    public function checkGitattributes(string $projectRoot): array
    {
        $path = rtrim($projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.gitattributes';

        if (! is_file($path)) {
            return ["Missing .gitattributes at: {$path}"];
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            return ["Unable to read .gitattributes at: {$path}"];
        }

        $lines = array_filter(array_map(
            static fn (string $line): string => preg_replace('/\s+/', ' ', trim($line)) ?? '',
            preg_split('/\R/', $contents) ?: [],
        ));

        $lineSet = array_fill_keys($lines, true);
        $violations = [];

        foreach (self::REQUIRED_EXPORT_IGNORES as $requiredLine) {
            if (! isset($lineSet[$requiredLine])) {
                $violations[] = "Missing .gitattributes export-ignore rule: {$requiredLine}";
            }
        }

        return $violations;
    }
}

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $zipPath = $argv[1] ?? null;
    $projectRoot = $argv[2] ?? dirname(__DIR__);

    if (! is_string($zipPath) || $zipPath === '') {
        fwrite(STDERR, "Usage: php tools/check-archive.php <archive.zip> [project-root]\n");
        exit(2);
    }

    $checker = new ArchiveHygieneChecker;
    $violations = $checker->check($zipPath, (string) $projectRoot);

    if ($violations !== []) {
        foreach ($violations as $violation) {
            fwrite(STDERR, $violation."\n");
        }

        exit(1);
    }

    fwrite(STDOUT, "Archive hygiene passed for {$zipPath}.\n");
}
