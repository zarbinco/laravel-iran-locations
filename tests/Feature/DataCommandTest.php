<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Zarbin\IranLocations\Tests\TestCase;

class DataCommandTest extends TestCase
{
    public function test_status_command_shows_package_data_version_counts_and_missing_database_tables(): void
    {
        $counts = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/data/manifest.json'), true, 512, JSON_THROW_ON_ERROR)['counts'];

        Artisan::call('iran-locations:status');

        $output = Artisan::output();

        self::assertStringContainsString('Package data status', $output);
        self::assertStringContainsString('Data version: 0.1.0-dev', $output);

        foreach ($counts as $dataset => $count) {
            self::assertStringContainsString("{$dataset}: {$count}", $output);
        }

        self::assertStringContainsString('Database status', $output);
        self::assertStringContainsString('Database tables: missing', $output);
        self::assertStringContainsString('database provinces: missing', $output);
        self::assertStringContainsString('Latest applied database data version: none', $output);
        self::assertStringContainsString('Database appears synced: no', $output);
    }

    public function test_doctor_command_reports_package_data_validation_result(): void
    {
        $exitCode = Artisan::call('iran-locations:doctor');
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Package data validation: passed', $output);
        self::assertStringContainsString('Configured models: passed', $output);
        self::assertStringContainsString('Database tables: missing', $output);
        self::assertStringContainsString('No database records were inspected or modified.', $output);
    }
}
