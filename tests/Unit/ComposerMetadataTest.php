<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\IranLocationsServiceProvider;
use Zarbin\IranLocations\Tests\TestCase;

class ComposerMetadataTest extends TestCase
{
    public function test_composer_metadata_is_release_ready(): void
    {
        $composer = $this->composer();

        self::assertSame('zarbinco/laravel-iran-locations', $composer['name']);
        self::assertSame('library', $composer['type']);
        self::assertSame('^8.2', $composer['require']['php']);
        self::assertSame('^0.1|^1.0', $composer['require']['zarbinco/laravel-persian-core']);
        self::assertStringContainsString('^0.1', $composer['require']['zarbinco/laravel-persian-core']);
        self::assertStringContainsString('^1.0', $composer['require']['zarbinco/laravel-persian-core']);
        self::assertArrayNotHasKey('ext-zip', $composer['require']);
        self::assertSame('*', $composer['require-dev']['ext-zip']);
        self::assertSame('stable', $composer['minimum-stability']);
        self::assertTrue($composer['prefer-stable']);

        foreach (['illuminate/contracts', 'illuminate/database', 'illuminate/routing', 'illuminate/support'] as $package) {
            self::assertSame('^11.0|^12.0|^13.0', $composer['require'][$package]);
        }

        self::assertSame('src/', $composer['autoload']['psr-4']['Zarbin\\IranLocations\\']);
        self::assertContains(IranLocationsServiceProvider::class, $composer['extra']['laravel']['providers']);
        self::assertSame('Zarbin\\IranLocations\\Facades\\IranLocations', $composer['extra']['laravel']['aliases']['IranLocations']);
    }

    public function test_composer_scripts_and_dependencies_remain_minimal(): void
    {
        $composer = $this->composer();

        foreach (['archive:check', 'test', 'test:ci', 'analyse', 'format', 'format:test', 'release:check', 'test:coverage'] as $script) {
            self::assertArrayHasKey($script, $composer['scripts']);
        }

        self::assertSame(['@test', '@format:test', '@analyse'], $composer['scripts']['test:ci']);
        self::assertSame([
            'composer validate --strict',
            '@test',
            '@format:test',
            '@analyse',
            'composer archive --format=zip --dir=build/release-check --file=laravel-iran-locations-release-check',
            'php tools/check-archive.php build/release-check/laravel-iran-locations-release-check.zip',
        ], $composer['scripts']['release:check']);

        self::assertArrayNotHasKey('spatie/laravel-query-builder', $composer['require']);

        foreach (['livewire/livewire', 'inertiajs/inertia-laravel', 'laravel/ui'] as $package) {
            self::assertArrayNotHasKey($package, $composer['require']);
            self::assertArrayNotHasKey($package, $composer['require-dev']);
        }

        foreach ($composer['require'] as $constraint) {
            self::assertStringNotContainsString('dev-main', (string) $constraint);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function composer(): array
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/composer.json');

        self::assertIsString($contents);

        $composer = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($composer);

        return $composer;
    }
}
