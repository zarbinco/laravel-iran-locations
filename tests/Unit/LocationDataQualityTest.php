<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Support\LocationModelResolver;
use Zarbin\IranLocations\Sync\LocationSyncService;
use Zarbin\IranLocations\Tests\TestCase;

class LocationDataQualityTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private const EXPECTED_PROVINCE_CAPITALS = [
        'تهران' => 'تهران',
        'مرکزی' => 'اراک',
        'گیلان' => 'رشت',
        'مازندران' => 'ساری',
        'آذربایجان شرقی' => 'تبریز',
        'آذربایجان غربی' => 'ارومیه',
        'کرمانشاه' => 'کرمانشاه',
        'خوزستان' => 'اهواز',
        'فارس' => 'شیراز',
        'کرمان' => 'کرمان',
        'خراسان رضوی' => 'مشهد',
        'اصفهان' => 'اصفهان',
        'سیستان و بلوچستان' => 'زاهدان',
        'کردستان' => 'سنندج',
        'همدان' => 'همدان',
        'چهارمحال و بختیاری' => 'شهرکرد',
        'لرستان' => 'خرم‌آباد',
        'ایلام' => 'ایلام',
        'کهگیلویه و بویراحمد' => 'یاسوج',
        'بوشهر' => 'بوشهر',
        'زنجان' => 'زنجان',
        'سمنان' => 'سمنان',
        'یزد' => 'یزد',
        'هرمزگان' => 'بندر عباس',
        'اردبیل' => 'اردبیل',
        'قم' => 'قم',
        'قزوین' => 'قزوین',
        'گلستان' => 'گرگان',
        'خراسان شمالی' => 'بجنورد',
        'خراسان جنوبی' => 'بیرجند',
        'البرز' => 'کرج',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_DUPLICATE_NEIGHBORHOODS = [
        'ir.city.001.001.001.001|ir.city.tehran.region.09|استاد معین' => [
            'ir.neighborhood.tehran.region.09.001',
            'ir.neighborhood.tehran.region.09.009',
        ],
        'ir.city.001.001.001.001|ir.city.tehran.region.15|مشیریه' => [
            'ir.neighborhood.tehran.region.15.014',
            'ir.neighborhood.tehran.region.15.025',
        ],
    ];

    public function test_public_persian_data_fields_use_persian_characters_and_clean_whitespace(): void
    {
        foreach ($this->datasets() as $dataset => $records) {
            foreach ($records as $index => $record) {
                foreach ($this->publicPersianFields($dataset) as $field) {
                    $value = $record[$field] ?? null;

                    if (! is_string($value)) {
                        continue;
                    }

                    self::assertDoesNotMatchRegularExpression('/[كي]/u', $value, "{$dataset}[{$index}].{$field} contains Arabic kaf/yeh.");
                    self::assertSame(trim($value), $value, "{$dataset}[{$index}].{$field} contains leading or trailing whitespace.");
                }
            }
        }
    }

    public function test_datasets_have_unique_codes_or_unique_mapping_keys(): void
    {
        foreach ($this->datasets() as $dataset => $records) {
            $seen = [];

            foreach ($records as $index => $record) {
                $key = $this->uniqueKey($dataset, $record);

                if ($key === null) {
                    continue;
                }

                self::assertArrayNotHasKey($key, $seen, "Dataset [{$dataset}] contains duplicate key [{$key}] at record [{$index}].");
                $seen[$key] = true;
            }
        }
    }

    public function test_manifest_counts_and_checksum_match_actual_datasets(): void
    {
        $manifest = $this->manifest();
        $datasets = $this->datasets();

        foreach (LocationDataManifest::datasets() as $dataset) {
            self::assertSame(count($datasets[$dataset]), $manifest['counts'][$dataset] ?? null, "Manifest count mismatch for [{$dataset}].");
        }

        $payload = [];

        foreach (LocationDataManifest::datasets() as $dataset) {
            $payload[$dataset] = $datasets[$dataset];
        }

        $checksum = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        self::assertSame($checksum, $manifest['checksum'] ?? null);
        self::assertSame('0.2.0-dev', $manifest['data_version'] ?? null);
        self::assertFalse($manifest['contains']['city_areas'] ?? true);
        self::assertFalse($manifest['contains']['aliases'] ?? true);
        self::assertSame(0, count($datasets['city_areas']));
        self::assertSame(0, count($datasets['aliases']));
    }

    public function test_packaged_data_references_are_valid(): void
    {
        $datasets = $this->datasets();
        $codes = $this->codeMaps($datasets);

        foreach ($datasets['counties'] as $index => $county) {
            self::assertArrayHasKey((string) ($county['province_code'] ?? ''), $codes['provinces'], "counties[{$index}] has an invalid province_code.");
        }

        foreach ($datasets['official_districts'] as $index => $district) {
            self::assertArrayHasKey((string) ($district['province_code'] ?? ''), $codes['provinces'], "official_districts[{$index}] has an invalid province_code.");
            self::assertArrayHasKey((string) ($district['county_code'] ?? ''), $codes['counties'], "official_districts[{$index}] has an invalid county_code.");
        }

        foreach ($datasets['rural_districts'] as $index => $district) {
            self::assertArrayHasKey((string) ($district['province_code'] ?? ''), $codes['provinces'], "rural_districts[{$index}] has an invalid province_code.");
            self::assertArrayHasKey((string) ($district['county_code'] ?? ''), $codes['counties'], "rural_districts[{$index}] has an invalid county_code.");
            self::assertArrayHasKey((string) ($district['official_district_code'] ?? ''), $codes['official_districts'], "rural_districts[{$index}] has an invalid official_district_code.");
        }

        foreach ($datasets['cities'] as $index => $city) {
            self::assertArrayHasKey((string) ($city['province_code'] ?? ''), $codes['provinces'], "cities[{$index}] has an invalid province_code.");
            $this->assertOptionalCodeExists($city['county_code'] ?? null, $codes['counties'], "cities[{$index}] has an invalid county_code.");
            $this->assertOptionalCodeExists($city['official_district_code'] ?? null, $codes['official_districts'], "cities[{$index}] has an invalid official_district_code.");
        }

        foreach ($datasets['city_regions'] as $index => $region) {
            self::assertArrayHasKey((string) ($region['city_code'] ?? ''), $codes['cities'], "city_regions[{$index}] has an invalid city_code.");
        }

        foreach ($datasets['city_areas'] as $index => $area) {
            self::assertArrayHasKey((string) ($area['city_region_code'] ?? ''), $codes['city_regions'], "city_areas[{$index}] has an invalid city_region_code.");
        }

        foreach ($datasets['neighborhoods'] as $index => $neighborhood) {
            self::assertArrayHasKey((string) ($neighborhood['city_code'] ?? ''), $codes['cities'], "neighborhoods[{$index}] has an invalid city_code.");
            $this->assertOptionalCodeExists($neighborhood['default_city_region_code'] ?? null, $codes['city_regions'], "neighborhoods[{$index}] has an invalid default_city_region_code.");
            $this->assertOptionalCodeExists($neighborhood['default_city_area_code'] ?? null, $codes['city_areas'], "neighborhoods[{$index}] has an invalid default_city_area_code.");
        }

        foreach ($datasets['neighborhood_region'] as $index => $mapping) {
            self::assertArrayHasKey((string) ($mapping['neighborhood_code'] ?? ''), $codes['neighborhoods'], "neighborhood_region[{$index}] has an invalid neighborhood_code.");
            self::assertArrayHasKey((string) ($mapping['city_region_code'] ?? ''), $codes['city_regions'], "neighborhood_region[{$index}] has an invalid city_region_code.");
        }

        foreach ($datasets['aliases'] as $index => $alias) {
            $locationType = (string) ($alias['location_type'] ?? '');
            $dataset = LocationModelResolver::datasetForLocationType($locationType);

            self::assertArrayHasKey((string) ($alias['location_code'] ?? ''), $codes[$dataset], "aliases[{$index}] has an invalid location_code.");
        }
    }

    public function test_province_capital_flags_match_expected_data_contract(): void
    {
        $provinces = $this->readDataset('provinces');
        $cities = $this->readDataset('cities');
        $provincesByName = [];
        $capitalsByProvince = [];

        self::assertCount(31, $provinces);

        foreach ($provinces as $province) {
            $provincesByName[$this->nameKey((string) $province['name_fa'])] = $province;
        }

        foreach ($cities as $city) {
            if (($city['is_province_capital'] ?? false) === true) {
                $capitalsByProvince[(string) $city['province_code']][] = $city;
            }
        }

        self::assertSame(31, array_sum(array_map('count', $capitalsByProvince)));

        foreach ($provinces as $province) {
            self::assertCount(1, $capitalsByProvince[(string) $province['code']] ?? [], "Province [{$province['code']}] must have exactly one capital city.");
        }

        foreach (self::EXPECTED_PROVINCE_CAPITALS as $provinceName => $capitalName) {
            $province = $provincesByName[$this->nameKey($provinceName)] ?? null;

            self::assertIsArray($province, "Expected province [{$provinceName}] was not found.");

            $capital = ($capitalsByProvince[(string) $province['code']] ?? [])[0] ?? null;

            self::assertIsArray($capital, "Capital for province [{$provinceName}] was not found.");
            self::assertSame((string) $province['code'], (string) $capital['province_code']);
            self::assertSame($this->nameKey($capitalName), $this->nameKey((string) $capital['name_fa']));
        }
    }

    public function test_duplicate_neighborhood_names_are_limited_to_documented_source_ambiguities(): void
    {
        $duplicates = [];

        foreach ($this->readDataset('neighborhoods') as $neighborhood) {
            if (($neighborhood['is_active'] ?? true) !== true) {
                continue;
            }

            $key = implode('|', [
                (string) ($neighborhood['city_code'] ?? ''),
                (string) ($neighborhood['default_city_region_code'] ?? ''),
                (string) ($neighborhood['name_fa'] ?? ''),
            ]);

            $duplicates[$key][] = (string) ($neighborhood['code'] ?? '');
        }

        $duplicates = array_filter($duplicates, static fn (array $codes): bool => count($codes) > 1);
        ksort($duplicates);

        self::assertSame(self::ALLOWED_DUPLICATE_NEIGHBORHOODS, $duplicates);
    }

    public function test_city_capital_builder_filter_returns_packaged_capitals_after_sync(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');
        $this->artisan('migrate')->run();

        $this->app->make(LocationSyncService::class)->sync();

        self::assertSame(31, City::query()->filter(['is_capital' => true])->count());
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function datasets(): array
    {
        $datasets = [];

        foreach (LocationDataManifest::datasets() as $dataset) {
            $datasets[$dataset] = $this->readDataset($dataset);
        }

        return $datasets;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readDataset(string $dataset): array
    {
        $data = json_decode((string) file_get_contents($this->dataPath(LocationDataManifest::fileFor($dataset))), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($data);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $manifest = json_decode((string) file_get_contents($this->dataPath(LocationDataManifest::MANIFEST_FILE)), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($manifest);

        return $manifest;
    }

    private function dataPath(string $file): string
    {
        return dirname(__DIR__, 2).'/data/'.$file;
    }

    /**
     * @return array<int, string>
     */
    private function publicPersianFields(string $dataset): array
    {
        return match ($dataset) {
            'aliases' => ['alias'],
            'neighborhood_region' => [],
            default => ['name_fa', 'display_name_fa'],
        };
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function uniqueKey(string $dataset, array $record): ?string
    {
        if ($dataset === 'neighborhood_region') {
            return (string) ($record['neighborhood_code'] ?? '').'|'.(string) ($record['city_region_code'] ?? '');
        }

        if ($dataset === 'aliases') {
            if (! is_string($record['location_type'] ?? null) || ! is_string($record['location_code'] ?? null) || ! is_string($record['normalized_alias'] ?? null)) {
                return null;
            }

            return LocationModelResolver::normalizeLocationType($record['location_type']).'|'.$record['location_code'].'|'.$record['normalized_alias'];
        }

        return is_string($record['code'] ?? null) ? $record['code'] : null;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     * @return array<string, array<string, true>>
     */
    private function codeMaps(array $datasets): array
    {
        $maps = [];

        foreach (['provinces', 'counties', 'official_districts', 'rural_districts', 'cities', 'city_regions', 'city_areas', 'neighborhoods'] as $dataset) {
            $maps[$dataset] = [];

            foreach ($datasets[$dataset] as $record) {
                if (is_string($record['code'] ?? null)) {
                    $maps[$dataset][$record['code']] = true;
                }
            }
        }

        return $maps;
    }

    /**
     * @param  array<string, true>  $codes
     */
    private function assertOptionalCodeExists(mixed $code, array $codes, string $message): void
    {
        if ($code === null || $code === '') {
            return;
        }

        self::assertIsString($code, $message);
        self::assertArrayHasKey($code, $codes, $message);
    }

    private function nameKey(string $value): string
    {
        return $this->app->make(LocationNormalizer::class)->search($value);
    }
}
