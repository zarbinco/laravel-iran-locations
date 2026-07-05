<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Tests\TestCase;

class ReleaseContractTest extends TestCase
{
    public function test_removed_config_keys_stay_absent_and_release_defaults_stay_safe(): void
    {
        /** @var array<string, mixed> $config */
        $config = require dirname(__DIR__, 2).'/config/iran-locations.php';

        /** @var array<string, mixed> $normalization */
        $normalization = $config['normalization'];
        /** @var array<string, mixed> $data */
        $data = $config['data'];
        /** @var array<string, mixed> $admin */
        $admin = $config['admin'];
        /** @var array<string, mixed> $api */
        $api = $config['api'];

        self::assertArrayNotHasKey('on_sync', $normalization);
        self::assertArrayNotHasKey('preserve_custom_records', $data);
        self::assertFalse($data['allow_package_record_direct_edit']);
        self::assertFalse($admin['enabled']);
        self::assertFalse($api['enabled']);
        self::assertSame(['api'], $api['middleware']);
    }

    public function test_release_gate_scripts_are_declared_for_local_and_ci_use(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/composer.json');

        self::assertIsString($contents);

        /** @var array<string, mixed> $composer */
        $composer = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        /** @var array<string, mixed> $scripts */
        $scripts = $composer['scripts'];

        self::assertSame(['@test', '@format:test', '@analyse'], $scripts['test:ci']);
        self::assertSame([
            'composer archive --format=zip --dir=build/release-check --file=laravel-iran-locations-release-check',
            'php tools/check-archive.php build/release-check/laravel-iran-locations-release-check.zip',
        ], $scripts['archive:check']);
        self::assertSame([
            'composer validate --strict',
            '@test',
            '@format:test',
            '@analyse',
            '@archive:check',
        ], $scripts['release:check']);
    }

    public function test_ci_workflow_explicitly_enables_zip_extension(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/tests.yml');

        self::assertIsString($contents);
        self::assertSame(2, substr_count($contents, 'extensions: zip'));
    }

    public function test_ci_workflow_uses_temporary_matrix_constraints_without_mutating_composer_metadata(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/tests.yml');

        self::assertIsString($contents);
        self::assertStringNotContainsString('composer require --no-update', $contents);
        self::assertStringNotContainsString('composer require --dev --no-update', $contents);

        foreach ([
            '--with "illuminate/contracts:${{ matrix.laravel }}"',
            '--with "illuminate/database:${{ matrix.laravel }}"',
            '--with "illuminate/routing:${{ matrix.laravel }}"',
            '--with "illuminate/support:${{ matrix.laravel }}"',
            '--with "orchestra/testbench:${{ matrix.testbench }}"',
        ] as $temporaryConstraint) {
            self::assertStringContainsString($temporaryConstraint, $contents);
        }

        self::assertStringContainsString('composer update \\', $contents);
        self::assertStringContainsString('--with-all-dependencies', $contents);
        self::assertStringNotContainsString("--with-all-dependencies \\\n            \"illuminate/contracts:", $contents);
        self::assertSame(2, substr_count($contents, 'extensions: zip'));
        self::assertStringContainsString('COMPOSER_POLICY_ADVISORIES_BLOCK: "0"', $contents);
        self::assertStringContainsString('composer audit || true', $contents);
        self::assertStringNotContainsString('policy.advisories.block false', $contents);
        self::assertStringNotContainsString('composer.lock', $contents);
    }
}
