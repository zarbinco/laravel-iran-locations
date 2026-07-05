<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Coding\LocationCodeGenerator;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Tests\TestCase;

class GeneratedDataTest extends TestCase
{
    public function test_generated_package_data_has_expected_initial_counts(): void
    {
        $expected = [
            'provinces' => 31,
            'counties' => 484,
            'official_districts' => 1087,
            'rural_districts' => 73,
            'cities' => 1456,
            'city_regions' => 22,
            'city_areas' => 0,
            'neighborhoods' => 568,
            'neighborhood_region' => 568,
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

        self::assertSame('0.2.0-dev', $manifest['data_version']);
        self::assertSame('IR', $manifest['country_code']);
        self::assertSame('excel-import', $manifest['source']['name']);
        self::assertSame('excel-initial', $manifest['source']['version']);
        self::assertSame([
            'iran-city.xlsx',
            'tehran-province-city.xlsx',
            'tehran-state-neighbers.xlsx',
        ], $manifest['source']['files']);
        self::assertTrue($manifest['contains']['provinces']);
        self::assertTrue($manifest['contains']['counties']);
        self::assertTrue($manifest['contains']['official_districts']);
        self::assertTrue($manifest['contains']['rural_districts']);
        self::assertTrue($manifest['contains']['cities']);
        self::assertTrue($manifest['contains']['city_regions']);
        self::assertTrue($manifest['contains']['neighborhoods']);
        self::assertTrue($manifest['contains']['neighborhood_region']);
        self::assertFalse($manifest['contains']['city_areas']);
        self::assertFalse($manifest['contains']['aliases']);
        self::assertIsString($manifest['checksum']);
        self::assertNotSame('', $manifest['checksum']);
        self::assertSame(LocationCodeGenerator::scheme(), $manifest['code_scheme']);
    }

    public function test_package_data_does_not_expose_public_districts_dataset(): void
    {
        self::assertNotContains('districts', LocationDataManifest::datasets());
        self::assertFileDoesNotExist(dirname(__DIR__, 2).'/data/districts.json');
        self::assertFileDoesNotExist(dirname(__DIR__, 2).'/src/Models/District.php');
        self::assertNotContains('iran_districts', config('iran-locations.tables'));
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
