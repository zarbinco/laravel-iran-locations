<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Tests\TestCase;

class GeneratedDataTest extends TestCase
{
    public function test_generated_package_data_has_expected_initial_counts(): void
    {
        $expected = [
            'provinces' => 31,
            'cities' => 1226,
            'city_regions' => 0,
            'city_areas' => 0,
            'neighborhoods' => 505,
            'aliases' => 0,
        ];

        $manifest = $this->readJson('manifest.json');

        self::assertSame($expected, $manifest['counts']);

        foreach ($expected as $dataset => $count) {
            self::assertCount($count, $this->readJson(LocationDataManifest::fileFor($dataset)));
        }
    }

    public function test_manifest_has_expected_initial_metadata(): void
    {
        $manifest = $this->readJson('manifest.json');

        self::assertSame('0.1.0-dev', $manifest['data_version']);
        self::assertSame('IR', $manifest['country_code']);
        self::assertTrue($manifest['contains']['provinces']);
        self::assertTrue($manifest['contains']['cities']);
        self::assertTrue($manifest['contains']['neighborhoods']);
        self::assertFalse($manifest['contains']['city_regions']);
        self::assertFalse($manifest['contains']['city_areas']);
        self::assertFalse($manifest['contains']['aliases']);
        self::assertIsString($manifest['checksum']);
        self::assertNotSame('', $manifest['checksum']);
    }

    public function test_package_data_does_not_expose_public_districts_dataset(): void
    {
        self::assertNotContains('districts', LocationDataManifest::datasets());
        self::assertFileDoesNotExist(dirname(__DIR__, 2).'/data/districts.json');
    }

    public function test_generated_neighborhood_records_include_type_when_present(): void
    {
        $records = $this->readJson('neighborhoods.json');

        foreach ($records as $record) {
            self::assertArrayHasKey('type', $record);
            self::assertNotSame('', $record['type']);
        }
    }

    /**
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    private function readJson(string $file): array
    {
        $data = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/data/'.$file), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($data);

        return $data;
    }
}
