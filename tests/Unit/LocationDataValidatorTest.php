<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Data\JsonLocationDataRepository;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Data\LocationDataValidator;
use Zarbin\IranLocations\Tests\TestCase;

class LocationDataValidatorTest extends TestCase
{
    public function test_validator_passes_for_package_data_files(): void
    {
        $result = $this->app->make(LocationDataValidator::class)->validate();

        self::assertTrue($result['ok'], implode(PHP_EOL, $result['errors']));
    }

    public function test_validator_catches_duplicate_codes(): void
    {
        $path = $this->makeDataPath([
            'provinces' => [
                $this->province(['code' => 'ir.province.001']),
                $this->province(['code' => 'ir.province.001', 'source_id' => 2]),
            ],
        ]);

        $result = $this->validatePath($path);

        self::assertFalse($result['ok']);
        self::assertContains('Dataset [provinces] contains duplicate code [ir.province.001].', $result['errors']);
    }

    public function test_validator_catches_city_with_missing_province_code(): void
    {
        $path = $this->makeDataPath([
            'cities' => [
                $this->city(['province_code' => 'ir.province.999']),
            ],
        ]);

        $result = $this->validatePath($path);

        self::assertFalse($result['ok']);
        self::assertContains('Dataset [cities] record [0] references missing province_code [ir.province.999].', $result['errors']);
    }

    public function test_validator_catches_county_with_missing_province_code(): void
    {
        $path = $this->makeDataPath([
            'counties' => [
                $this->county(['province_code' => 'ir.province.999']),
            ],
        ]);

        $result = $this->validatePath($path);

        self::assertFalse($result['ok']);
        self::assertContains('Dataset [counties] record [0] references missing province_code [ir.province.999].', $result['errors']);
    }

    public function test_validator_catches_official_district_with_missing_county_code(): void
    {
        $path = $this->makeDataPath([
            'official_districts' => [
                $this->officialDistrict(['county_code' => 'ir.county.001.999']),
            ],
        ]);

        $result = $this->validatePath($path);

        self::assertFalse($result['ok']);
        self::assertContains('Dataset [official_districts] record [0] references missing county_code [ir.county.001.999].', $result['errors']);
    }

    public function test_validator_catches_rural_district_with_missing_official_district_code(): void
    {
        $path = $this->makeDataPath([
            'rural_districts' => [
                $this->ruralDistrict(['official_district_code' => 'ir.official_district.001.001.999']),
            ],
        ]);

        $result = $this->validatePath($path);

        self::assertFalse($result['ok']);
        self::assertContains('Dataset [rural_districts] record [0] references missing official_district_code [ir.official_district.001.001.999].', $result['errors']);
    }

    public function test_validator_catches_city_with_missing_official_district_code(): void
    {
        $path = $this->makeDataPath([
            'cities' => [
                $this->city(['official_district_code' => 'ir.official_district.001.001.999']),
            ],
        ]);

        $result = $this->validatePath($path);

        self::assertFalse($result['ok']);
        self::assertContains('Dataset [cities] record [0] references missing official_district_code [ir.official_district.001.001.999].', $result['errors']);
    }

    public function test_validator_catches_neighborhood_with_missing_city_code(): void
    {
        $path = $this->makeDataPath([
            'neighborhoods' => [
                $this->neighborhood(['city_code' => 'ir.city.001.9999']),
            ],
        ]);

        $result = $this->validatePath($path);

        self::assertFalse($result['ok']);
        self::assertContains('Dataset [neighborhoods] record [0] references missing city_code [ir.city.001.9999].', $result['errors']);
    }

    public function test_validator_catches_manifest_count_mismatch(): void
    {
        $path = $this->makeDataPath([], [
            'counts' => [
                'provinces' => 2,
            ],
        ]);

        $result = $this->validatePath($path);

        self::assertFalse($result['ok']);
        self::assertContains('Manifest count for [provinces] is [2], actual count is [1].', $result['errors']);
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     * @param  array<string, mixed>  $manifestOverrides
     */
    private function makeDataPath(array $datasets = [], array $manifestOverrides = []): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'iran-locations-data-'.bin2hex(random_bytes(6));

        mkdir($path, 0775, true);

        $datasets = array_replace([
            'provinces' => [$this->province()],
            'counties' => [$this->county()],
            'official_districts' => [$this->officialDistrict()],
            'rural_districts' => [$this->ruralDistrict()],
            'cities' => [$this->city()],
            'city_regions' => [$this->cityRegion()],
            'city_areas' => [],
            'neighborhoods' => [$this->neighborhood()],
            'neighborhood_region' => [$this->neighborhoodRegion()],
            'aliases' => [],
        ], $datasets);

        foreach (LocationDataManifest::datasetFiles() as $dataset => $file) {
            $this->writeJson($path.DIRECTORY_SEPARATOR.$file, $datasets[$dataset] ?? []);
        }

        $counts = [];

        foreach (LocationDataManifest::datasets() as $dataset) {
            $counts[$dataset] = count($datasets[$dataset] ?? []);
        }

        $manifest = array_replace_recursive([
            'data_version' => '0.2.0-dev',
            'country_code' => 'IR',
            'source' => [
                'name' => 'test',
                'version' => 'excel-initial',
                'files' => [],
            ],
            'contains' => [
                'provinces' => true,
                'counties' => true,
                'official_districts' => true,
                'rural_districts' => true,
                'cities' => true,
                'city_regions' => true,
                'city_areas' => false,
                'neighborhoods' => true,
                'neighborhood_region' => true,
                'aliases' => false,
            ],
            'generated_at' => null,
            'counts' => $counts,
            'checksum' => null,
        ], $manifestOverrides);

        $this->writeJson($path.DIRECTORY_SEPARATOR.LocationDataManifest::MANIFEST_FILE, $manifest);

        return $path;
    }

    /**
     * @return array{ok: bool, errors: array<int, string>, checks: array<int, string>}
     */
    private function validatePath(string $path): array
    {
        return (new LocationDataValidator(new JsonLocationDataRepository($path)))->validate();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function province(array $overrides = []): array
    {
        return array_replace([
            'code' => 'ir.province.001',
            'source_id' => 1,
            'name_fa' => 'تهران',
            'name_en' => null,
            'slug' => 'province-1',
            'normalized_name' => 'تهران',
            'display_name_fa' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => 'excel-initial',
            'data_version' => '0.2.0-dev',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function county(array $overrides = []): array
    {
        return array_replace([
            'code' => 'ir.county.001.001',
            'source_id' => 1,
            'province_code' => 'ir.province.001',
            'province_source_id' => 1,
            'name_fa' => 'تهران',
            'name_en' => null,
            'slug' => 'tehran-county',
            'normalized_name' => 'تهران',
            'display_name_fa' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => 'excel-initial',
            'data_version' => '0.2.0-dev',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function officialDistrict(array $overrides = []): array
    {
        return array_replace([
            'code' => 'ir.official_district.001.001.001',
            'source_id' => 1,
            'province_code' => 'ir.province.001',
            'county_code' => 'ir.county.001.001',
            'county_source_id' => 1,
            'name_fa' => 'مرکزی',
            'name_en' => null,
            'slug' => 'markazi',
            'normalized_name' => 'مرکزی',
            'display_name_fa' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => 'excel-initial',
            'data_version' => '0.2.0-dev',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function ruralDistrict(array $overrides = []): array
    {
        return array_replace([
            'code' => 'ir.rural_district.001.001.001.001',
            'source_id' => 1,
            'province_code' => 'ir.province.001',
            'county_code' => 'ir.county.001.001',
            'official_district_code' => 'ir.official_district.001.001.001',
            'name_fa' => 'سیاهرود',
            'name_en' => null,
            'slug' => 'siahroud',
            'normalized_name' => 'سیاهرود',
            'display_name_fa' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => 'excel-initial',
            'data_version' => '0.2.0-dev',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function city(array $overrides = []): array
    {
        return array_replace([
            'code' => 'ir.city.001.001.001.001',
            'source_id' => 1,
            'province_code' => 'ir.province.001',
            'province_source_id' => 1,
            'county_code' => 'ir.county.001.001',
            'county_source_id' => 1,
            'official_district_code' => 'ir.official_district.001.001.001',
            'official_district_source_id' => 1,
            'name_fa' => 'تهران',
            'name_en' => null,
            'slug' => 'tehran',
            'normalized_name' => 'تهران',
            'display_name_fa' => null,
            'is_province_capital' => false,
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => 'excel-initial',
            'data_version' => '0.2.0-dev',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function cityRegion(array $overrides = []): array
    {
        return array_replace([
            'code' => 'ir.city.tehran.region.01',
            'source_id' => 1,
            'city_code' => 'ir.city.001.001.001.001',
            'city_source_id' => 1,
            'name_fa' => 'منطقه ۱ تهران',
            'slug' => 'tehran-region-01',
            'normalized_name' => 'منطقه ۱ تهران',
            'is_active' => true,
            'source' => 'package',
            'source_version' => 'excel-initial',
            'data_version' => '0.2.0-dev',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function neighborhood(array $overrides = []): array
    {
        return array_replace([
            'code' => 'ir.neighborhood.001.0001.0001',
            'source_id' => 1,
            'city_code' => 'ir.city.001.001.001.001',
            'city_source_id' => 1,
            'name_fa' => 'آجودانیه',
            'name_en' => null,
            'slug' => 'neighborhood-1-1',
            'normalized_name' => 'آجودانیه',
            'display_name_fa' => null,
            'type' => 'neighborhood',
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => 'excel-initial',
            'data_version' => '0.2.0-dev',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function neighborhoodRegion(array $overrides = []): array
    {
        return array_replace([
            'neighborhood_code' => 'ir.neighborhood.001.0001.0001',
            'city_region_code' => 'ir.city.tehran.region.01',
            'is_primary' => true,
            'source' => 'package',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>|array<int, array<string, mixed>>  $data
     */
    private function writeJson(string $path, array $data): void
    {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
    }
}
