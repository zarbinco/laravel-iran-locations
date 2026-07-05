<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Data;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Zarbin\IranLocations\Coding\LocationCodeGenerator;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use ZipArchive;

final class ExcelLocationDataConverter
{
    private const DATA_VERSION = '0.2.0-dev';

    private const SOURCE_VERSION = 'excel-initial';

    private const NATIONWIDE_FILE = 'iran-city.xlsx';

    private const TEHRAN_OFFICIAL_FILE = 'tehran-province-city.xlsx';

    private const TEHRAN_MUNICIPAL_FILE = 'tehran-state-neighbers.xlsx';

    private readonly LocationCodeGenerator $codes;

    public function __construct(
        private readonly LocationNormalizer $normalizer,
        ?LocationCodeGenerator $codes = null,
    ) {
        $this->codes = $codes ?? new LocationCodeGenerator;
    }

    /**
     * @return array<string, mixed>
     */
    public function convertDirectory(string $sourcePath, string $outputPath): array
    {
        $nationwideRows = $this->readWorksheet($sourcePath, self::NATIONWIDE_FILE, 'لیست شهرهای ایران');
        $tehranOfficialRows = $this->readWorksheet($sourcePath, self::TEHRAN_OFFICIAL_FILE, '1');
        $tehranMunicipalRows = $this->readWorksheet($sourcePath, self::TEHRAN_MUNICIPAL_FILE, 'محله‌ها');

        return $this->convertRows($nationwideRows, $tehranOfficialRows, $tehranMunicipalRows, $outputPath);
    }

    /**
     * @param  array<int, array<int, mixed>>  $nationwideRows
     * @param  array<int, array<int, mixed>>  $tehranOfficialRows
     * @param  array<int, array<int, mixed>>  $tehranMunicipalRows
     * @return array<string, mixed>
     */
    public function convertRows(array $nationwideRows, array $tehranOfficialRows, array $tehranMunicipalRows, string $outputPath): array
    {
        $skipped = [];
        $duplicates = [];
        $missingReferences = [];
        $usedSlugs = [
            'provinces' => [],
            'counties' => [],
            'official_districts' => [],
            'rural_districts' => [],
            'cities' => [],
            'city_regions' => [],
            'neighborhoods' => [],
        ];

        $sourceRows = $this->nationwideSourceRows($nationwideRows, $skipped);
        $hierarchy = $this->buildOfficialHierarchy($sourceRows, $usedSlugs);
        $this->applyTehranOfficialDetails($tehranOfficialRows, $hierarchy, $usedSlugs, $skipped, $duplicates, $missingReferences);

        $municipal = $this->buildTehranMunicipalData($tehranMunicipalRows, $hierarchy['tehran_city'] ?? null, $usedSlugs, $skipped, $duplicates, $missingReferences);

        $datasets = [
            'provinces' => $hierarchy['provinces'],
            'counties' => $hierarchy['counties'],
            'official_districts' => $hierarchy['official_districts'],
            'rural_districts' => $hierarchy['rural_districts'],
            'cities' => $hierarchy['cities'],
            'city_regions' => $municipal['city_regions'],
            'city_areas' => [],
            'neighborhoods' => $municipal['neighborhoods'],
            'neighborhood_region' => $municipal['neighborhood_region'],
            'aliases' => [],
        ];

        $this->writeDatasets($outputPath, $datasets);
        $manifest = $this->manifestFor($datasets);
        $this->writeJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::MANIFEST_FILE, $manifest);

        return [
            'manifest' => $manifest,
            'counts' => $manifest['counts'],
            'skipped' => $skipped,
            'duplicates' => $duplicates,
            'missing_references' => $missingReferences,
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readWorksheet(string $sourcePath, string $file, string $sheet): array
    {
        $path = rtrim($sourcePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;

        if (! is_file($path)) {
            throw new RuntimeException("Excel source file [{$file}] was not found.");
        }

        return $this->readXlsxRows($path, $sheet);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>  $skipped
     * @return array<int, array<string, mixed>>
     */
    private function nationwideSourceRows(array $rows, array &$skipped): array
    {
        $records = [];

        foreach ($rows as $index => $row) {
            if ($index === 0 || $this->stringValue($row[0] ?? null) === 'کد استان') {
                continue;
            }

            $provinceCode = $this->stringValue($row[0] ?? null);
            $provinceName = $this->nameValue($row[1] ?? null);
            $countyCode = $this->stringValue($row[2] ?? null);
            $countyName = $this->nameValue($row[3] ?? null);
            $officialDistrictCode = $this->stringValue($row[4] ?? null);
            $officialDistrictName = $this->nameValue($row[5] ?? null);
            $cityName = $this->nameValue($row[6] ?? null);

            if ($provinceName === '' && $countyName === '' && $officialDistrictName === '' && $cityName === '') {
                continue;
            }

            if ($provinceName === '' || $countyName === '' || $officialDistrictName === '' || $cityName === '') {
                $skipped[] = 'iran-city.xlsx row ['.($index + 1).'] is missing a required hierarchy value.';

                continue;
            }

            $records[] = [
                'source_order' => $index,
                'province_source_code' => $provinceCode,
                'province_name' => $provinceName,
                'county_source_code' => $countyCode,
                'county_name' => $countyName,
                'official_district_source_code' => $officialDistrictCode,
                'official_district_name' => $officialDistrictName,
                'city_name' => $cityName,
            ];
        }

        return $records;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, array<string, true>>  $usedSlugs
     * @return array<string, mixed>
     */
    private function buildOfficialHierarchy(array $rows, array &$usedSlugs): array
    {
        $provinceKeys = $this->orderedProvinceKeys($rows);
        $provinceOrder = array_flip($provinceKeys);

        usort($rows, static function (array $first, array $second) use ($provinceOrder): int {
            $firstProvince = $first['province_key'];
            $secondProvince = $second['province_key'];

            return [$provinceOrder[$firstProvince] ?? PHP_INT_MAX, $first['source_order']]
                <=> [$provinceOrder[$secondProvince] ?? PHP_INT_MAX, $second['source_order']];
        });

        $provinces = [];
        $counties = [];
        $officialDistricts = [];
        $cities = [];
        $ruralDistricts = [];

        $provinceMeta = [];
        $countyMeta = [];
        $officialDistrictMeta = [];
        $cityMeta = [];
        $cityMetaByCountyAndName = [];
        $citySequenceByDistrict = [];
        $countySequenceByProvince = [];
        $officialSequenceByCounty = [];
        $tehranCity = null;

        foreach ($provinceKeys as $index => $provinceKey) {
            $row = $this->firstRowForProvince($rows, $provinceKey);
            $sourceId = $index + 1;
            $provinceSegment = $sourceId;
            $record = [
                'code' => $this->provinceCode($provinceSegment),
                'source_id' => $sourceId,
                'name_fa' => $row['province_name'],
                'name_en' => null,
                'slug' => $this->slug($row['province_name'], "province-{$sourceId}", $usedSlugs['provinces']),
                'normalized_name' => $this->normalizer->search($row['province_name']),
                'display_name_fa' => null,
                'is_active' => true,
                'source' => 'package',
                'source_version' => self::SOURCE_VERSION,
                'data_version' => self::DATA_VERSION,
            ];

            $provinces[] = $record;
            $provinceMeta[$provinceKey] = [
                'key' => $provinceKey,
                'segment' => $provinceSegment,
                'record' => $record,
                'name_key' => $this->nameKey($row['province_name']),
            ];
        }

        foreach ($rows as $row) {
            $provinceKey = $row['province_key'];
            $countyKey = $this->countyKey($row);
            $officialDistrictKey = $this->officialDistrictKey($row);
            $province = $provinceMeta[$provinceKey];

            if (! isset($countyMeta[$countyKey])) {
                $countySequenceByProvince[$provinceKey] = ($countySequenceByProvince[$provinceKey] ?? 0) + 1;
                $sourceId = count($counties) + 1;
                $countySegment = $countySequenceByProvince[$provinceKey];
                $record = [
                    'code' => $this->countyCode($province['segment'], $countySegment),
                    'source_id' => $sourceId,
                    'province_code' => $province['record']['code'],
                    'province_source_id' => $province['record']['source_id'],
                    'name_fa' => $row['county_name'],
                    'name_en' => null,
                    'slug' => $this->slug($row['county_name'], "county-{$sourceId}", $usedSlugs['counties']),
                    'normalized_name' => $this->normalizer->search($row['county_name']),
                    'display_name_fa' => null,
                    'is_active' => true,
                    'source' => 'package',
                    'source_version' => self::SOURCE_VERSION,
                    'data_version' => self::DATA_VERSION,
                ];

                $counties[] = $record;
                $countyMeta[$countyKey] = [
                    'key' => $countyKey,
                    'province_key' => $provinceKey,
                    'segment' => $countySegment,
                    'record' => $record,
                    'name_key' => $this->nameKey($row['county_name']),
                ];
            }

            $county = $countyMeta[$countyKey];

            if (! isset($officialDistrictMeta[$officialDistrictKey])) {
                $officialSequenceByCounty[$countyKey] = ($officialSequenceByCounty[$countyKey] ?? 0) + 1;
                $sourceId = count($officialDistricts) + 1;
                $officialDistrictSegment = $officialSequenceByCounty[$countyKey];
                $record = [
                    'code' => $this->officialDistrictCode($province['segment'], $county['segment'], $officialDistrictSegment),
                    'source_id' => $sourceId,
                    'province_code' => $province['record']['code'],
                    'county_code' => $county['record']['code'],
                    'county_source_id' => $county['record']['source_id'],
                    'name_fa' => $row['official_district_name'],
                    'name_en' => null,
                    'slug' => $this->slug($row['official_district_name'], "official-district-{$sourceId}", $usedSlugs['official_districts']),
                    'normalized_name' => $this->normalizer->search($row['official_district_name']),
                    'display_name_fa' => null,
                    'is_active' => true,
                    'source' => 'package',
                    'source_version' => self::SOURCE_VERSION,
                    'data_version' => self::DATA_VERSION,
                ];

                $officialDistricts[] = $record;
                $officialDistrictMeta[$officialDistrictKey] = [
                    'key' => $officialDistrictKey,
                    'province_key' => $provinceKey,
                    'county_key' => $countyKey,
                    'segment' => $officialDistrictSegment,
                    'record' => $record,
                    'name_key' => $this->nameKey($row['official_district_name']),
                ];
            }

            $officialDistrict = $officialDistrictMeta[$officialDistrictKey];
            $citySequenceByDistrict[$officialDistrictKey] = ($citySequenceByDistrict[$officialDistrictKey] ?? 0) + 1;
            $sourceId = count($cities) + 1;
            $citySegment = $citySequenceByDistrict[$officialDistrictKey];
            $cityRecord = [
                'code' => $this->cityCode($province['segment'], $county['segment'], $officialDistrict['segment'], $citySegment),
                'source_id' => $sourceId,
                'province_code' => $province['record']['code'],
                'province_source_id' => $province['record']['source_id'],
                'county_code' => $county['record']['code'],
                'county_source_id' => $county['record']['source_id'],
                'official_district_code' => $officialDistrict['record']['code'],
                'official_district_source_id' => $officialDistrict['record']['source_id'],
                'name_fa' => $row['city_name'],
                'name_en' => null,
                'slug' => $this->slug($row['city_name'], "city-{$sourceId}", $usedSlugs['cities']),
                'normalized_name' => $this->normalizer->search($row['city_name']),
                'display_name_fa' => null,
                'is_province_capital' => false,
                'latitude' => null,
                'longitude' => null,
                'is_active' => true,
                'source' => 'package',
                'source_version' => self::SOURCE_VERSION,
                'data_version' => self::DATA_VERSION,
            ];

            $cities[] = $cityRecord;
            $cityKey = $officialDistrictKey.'|city-'.$citySegment;
            $cityMeta[$cityKey] = [
                'key' => $cityKey,
                'province_key' => $provinceKey,
                'county_key' => $countyKey,
                'official_district_key' => $officialDistrictKey,
                'record' => $cityRecord,
                'name_key' => $this->nameKey($row['city_name']),
            ];
            $cityMetaByCountyAndName[$countyKey][$this->nameKey($row['city_name'])] = $cityMeta[$cityKey];

            if ($this->sameName($row['province_name'], 'تهران') && $this->sameName($row['city_name'], 'تهران') && $tehranCity === null) {
                $tehranCity = $cityRecord;
            }
        }

        return [
            'provinces' => $provinces,
            'counties' => $counties,
            'official_districts' => $officialDistricts,
            'rural_districts' => $ruralDistricts,
            'cities' => $cities,
            'province_meta' => $provinceMeta,
            'county_meta' => $countyMeta,
            'official_district_meta' => $officialDistrictMeta,
            'city_meta' => $cityMeta,
            'city_meta_by_county_and_name' => $cityMetaByCountyAndName,
            'county_sequence_by_province' => $countySequenceByProvince,
            'official_sequence_by_county' => $officialSequenceByCounty,
            'rural_sequence_by_official_district' => [],
            'rural_name_keys' => [],
            'tehran_city' => $tehranCity,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    private function orderedProvinceKeys(array &$rows): array
    {
        $names = [];
        $firstOrder = [];

        foreach ($rows as $index => $row) {
            $key = $this->provinceKey($row);
            $rows[$index]['province_key'] = $key;

            $names[$key] ??= $row['province_name'];
            $firstOrder[$key] ??= $row['source_order'];
        }

        $keys = array_keys($names);

        usort($keys, function (string $first, string $second) use ($names, $firstOrder): int {
            $firstIsTehran = $this->sameName($names[$first], 'تهران');
            $secondIsTehran = $this->sameName($names[$second], 'تهران');

            if ($firstIsTehran !== $secondIsTehran) {
                return $firstIsTehran ? -1 : 1;
            }

            return $firstOrder[$first] <=> $firstOrder[$second];
        });

        return $keys;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function firstRowForProvince(array $rows, string $provinceKey): array
    {
        foreach ($rows as $row) {
            if ($row['province_key'] === $provinceKey) {
                return $row;
            }
        }

        throw new RuntimeException("No rows were found for province key [{$provinceKey}].");
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $hierarchy
     * @param  array<string, array<string, true>>  $usedSlugs
     * @param  array<int, string>  $skipped
     * @param  array<int, string>  $duplicates
     * @param  array<int, string>  $missingReferences
     */
    private function applyTehranOfficialDetails(array $rows, array &$hierarchy, array &$usedSlugs, array &$skipped, array &$duplicates, array &$missingReferences): void
    {
        $tehranProvince = $this->tehranProvinceMeta($hierarchy);

        if ($tehranProvince === null) {
            $missingReferences[] = 'Tehran province was not found while applying Tehran official details.';

            return;
        }

        $countyByName = $this->countyMetaByName($hierarchy, $tehranProvince['key']);
        $officialByCountyAndName = $this->officialDistrictMetaByCountyAndName($hierarchy, $tehranProvince['key']);
        $currentCountyName = null;

        foreach ($rows as $index => $row) {
            $divisionName = $this->nameValue($row[0] ?? null);

            if ($divisionName === '' || $divisionName === 'شهرستان و بخش' || $this->startsWith($divisionName, 'تقسیمات')) {
                continue;
            }

            if ($this->startsWith($divisionName, 'شهرستان')) {
                $currentCountyName = $this->stripLeadingLabel($divisionName, 'شهرستان');

                continue;
            }

            if (! $this->startsWith($divisionName, 'بخش')) {
                $skipped[] = 'tehran-province-city.xlsx row ['.($index + 1)."] has unrecognized division label [{$divisionName}].";

                continue;
            }

            if ($currentCountyName === null || $currentCountyName === '') {
                $missingReferences[] = 'tehran-province-city.xlsx row ['.($index + 1).'] has a district without a county context.';

                continue;
            }

            $countyNameKey = $this->nameKey($currentCountyName);
            $county = $countyByName[$countyNameKey] ?? null;

            if ($county === null) {
                $county = $this->addCounty($currentCountyName, $tehranProvince, $hierarchy, $usedSlugs);
                $countyByName[$countyNameKey] = $county;
            }

            $officialDistrictName = $this->stripLeadingLabel($divisionName, 'بخش');
            $officialDistrictNameKey = $this->nameKey($officialDistrictName);
            $officialDistrict = $officialByCountyAndName[$county['key']][$officialDistrictNameKey] ?? null;

            if ($officialDistrict === null) {
                $officialDistrict = $this->officialDistrictFromCityList($this->stringValue($row[1] ?? null), $county, $hierarchy);
            }

            if ($officialDistrict === null) {
                $officialDistrict = $this->addOfficialDistrict($officialDistrictName, $tehranProvince, $county, $hierarchy, $usedSlugs);
                $officialByCountyAndName[$county['key']][$officialDistrictNameKey] = $officialDistrict;
            }

            foreach ($this->splitSourceList($this->stringValue($row[2] ?? null)) as $ruralDistrictName) {
                $ruralDistrictNameKey = $this->nameKey($ruralDistrictName);
                $dedupeKey = $officialDistrict['key'].'|'.$ruralDistrictNameKey;

                if (isset($hierarchy['rural_name_keys'][$dedupeKey])) {
                    $duplicates[] = "Duplicate rural district [{$ruralDistrictName}] in official district [{$officialDistrict['record']['name_fa']}].";

                    continue;
                }

                $hierarchy['rural_name_keys'][$dedupeKey] = true;
                $this->addRuralDistrict($ruralDistrictName, $tehranProvince, $county, $officialDistrict, $hierarchy, $usedSlugs);
            }
        }
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>|null  $tehranCity
     * @param  array<string, array<string, true>>  $usedSlugs
     * @param  array<int, string>  $skipped
     * @param  array<int, string>  $duplicates
     * @param  array<int, string>  $missingReferences
     * @return array{city_regions: array<int, array<string, mixed>>, neighborhoods: array<int, array<string, mixed>>, neighborhood_region: array<int, array<string, mixed>>}
     */
    private function buildTehranMunicipalData(array $rows, ?array $tehranCity, array &$usedSlugs, array &$skipped, array &$duplicates, array &$missingReferences): array
    {
        if ($tehranCity === null) {
            $missingReferences[] = 'Tehran city was not found while generating Tehran municipal data.';

            return [
                'city_regions' => [],
                'neighborhoods' => [],
                'neighborhood_region' => [],
            ];
        }

        $cityRegions = [];
        $cityRegionByNumber = [];
        $neighborhoods = [];
        $neighborhoodRegion = [];
        $seenNeighborhoodNames = [];

        foreach ($rows as $index => $row) {
            $sourceId = $this->intValue($row[0] ?? null, count($neighborhoods) + 1);
            $regionNumber = $this->intValue($row[1] ?? null, 0);
            $regionName = $this->nameValue($row[2] ?? null);
            $neighborhoodName = $this->nameValue($row[3] ?? null);
            $regionRowNumber = $this->intValue($row[4] ?? null, count($neighborhoods) + 1);

            if ($this->stringValue($row[0] ?? null) === 'ردیف' || $this->startsWith($this->stringValue($row[0] ?? null), 'لیست')) {
                continue;
            }

            if ($regionNumber <= 0 || $regionName === '' || $neighborhoodName === '') {
                if ($regionNumber !== 0 || $regionName !== '' || $neighborhoodName !== '') {
                    $skipped[] = 'tehran-state-neighbers.xlsx row ['.($index + 1).'] is missing region or neighborhood data.';
                }

                continue;
            }

            if (! isset($cityRegionByNumber[$regionNumber])) {
                $regionCode = $this->cityRegionCode((string) $tehranCity['code'], $regionNumber);
                $cityRegionByNumber[$regionNumber] = $regionCode;
                $cityRegions[] = [
                    'code' => $regionCode,
                    'source_id' => $regionNumber,
                    'city_code' => $tehranCity['code'],
                    'city_source_id' => $tehranCity['source_id'],
                    'number' => $regionNumber,
                    'name_fa' => $regionName,
                    'name_en' => null,
                    'slug' => $this->slug($regionName, sprintf('tehran-region-%02d', $regionNumber), $usedSlugs['city_regions']),
                    'normalized_name' => $this->normalizer->search($regionName),
                    'type' => 'municipal_region',
                    'display_name_fa' => null,
                    'is_active' => true,
                    'source' => 'package',
                    'source_version' => self::SOURCE_VERSION,
                    'data_version' => self::DATA_VERSION,
                ];
            }

            $neighborhoodNameKey = $this->nameKey($neighborhoodName);

            if (isset($seenNeighborhoodNames[$neighborhoodNameKey])) {
                $duplicates[] = "Neighborhood name [{$neighborhoodName}] appears in multiple Tehran municipal rows.";
            }

            $seenNeighborhoodNames[$neighborhoodNameKey] = true;
            $regionCode = $cityRegionByNumber[$regionNumber];
            $neighborhoodCode = $this->neighborhoodCode((string) $tehranCity['code'], $regionCode, $regionRowNumber);
            $neighborhoods[] = [
                'code' => $neighborhoodCode,
                'source_id' => $sourceId,
                'city_code' => $tehranCity['code'],
                'city_source_id' => $tehranCity['source_id'],
                'default_city_region_code' => $regionCode,
                'default_city_area_code' => null,
                'name_fa' => $neighborhoodName,
                'name_en' => null,
                'slug' => $this->slug($neighborhoodName, "tehran-neighborhood-{$sourceId}", $usedSlugs['neighborhoods']),
                'normalized_name' => $this->normalizer->search($neighborhoodName),
                'display_name_fa' => null,
                'type' => $this->neighborhoodType($neighborhoodName),
                'latitude' => null,
                'longitude' => null,
                'is_active' => true,
                'source' => 'package',
                'source_version' => self::SOURCE_VERSION,
                'data_version' => self::DATA_VERSION,
            ];
            $neighborhoodRegion[] = [
                'neighborhood_code' => $neighborhoodCode,
                'city_region_code' => $regionCode,
                'is_primary' => true,
                'source' => 'package',
                'confidence' => 100,
            ];
        }

        return [
            'city_regions' => $cityRegions,
            'neighborhoods' => $neighborhoods,
            'neighborhood_region' => $neighborhoodRegion,
        ];
    }

    /**
     * @param  array<string, mixed>  $hierarchy
     * @return array<string, mixed>|null
     */
    private function tehranProvinceMeta(array $hierarchy): ?array
    {
        foreach ($hierarchy['province_meta'] as $province) {
            if ($this->sameName($province['record']['name_fa'], 'تهران')) {
                return $province;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $hierarchy
     * @return array<string, array<string, mixed>>
     */
    private function countyMetaByName(array $hierarchy, string $provinceKey): array
    {
        $counties = [];

        foreach ($hierarchy['county_meta'] as $county) {
            if ($county['province_key'] === $provinceKey) {
                $counties[$county['name_key']] = $county;
            }
        }

        return $counties;
    }

    /**
     * @param  array<string, mixed>  $hierarchy
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function officialDistrictMetaByCountyAndName(array $hierarchy, string $provinceKey): array
    {
        $districts = [];

        foreach ($hierarchy['official_district_meta'] as $district) {
            if ($district['province_key'] === $provinceKey) {
                $districts[$district['county_key']][$district['name_key']] = $district;
            }
        }

        return $districts;
    }

    /**
     * @param  array<string, mixed>  $county
     * @param  array<string, mixed>  $hierarchy
     * @return array<string, mixed>|null
     */
    private function officialDistrictFromCityList(string $cityList, array $county, array $hierarchy): ?array
    {
        $officialDistrictKeys = [];

        foreach ($this->splitSourceList($cityList) as $cityName) {
            $city = $hierarchy['city_meta_by_county_and_name'][$county['key']][$this->nameKey($cityName)] ?? null;

            if (! is_array($city)) {
                continue;
            }

            $officialDistrictKeys[$city['official_district_key']] = true;
        }

        if (count($officialDistrictKeys) !== 1) {
            return null;
        }

        $officialDistrictKey = array_key_first($officialDistrictKeys);
        $officialDistrict = $hierarchy['official_district_meta'][$officialDistrictKey] ?? null;

        return is_array($officialDistrict) ? $officialDistrict : null;
    }

    /**
     * @param  array<string, mixed>  $province
     * @param  array<string, mixed>  $hierarchy
     * @param  array<string, array<string, true>>  $usedSlugs
     * @return array<string, mixed>
     */
    private function addCounty(string $name, array $province, array &$hierarchy, array &$usedSlugs): array
    {
        $hierarchy['county_sequence_by_province'][$province['key']] = ($hierarchy['county_sequence_by_province'][$province['key']] ?? 0) + 1;
        $sourceId = count($hierarchy['counties']) + 1;
        $segment = $hierarchy['county_sequence_by_province'][$province['key']];
        $record = [
            'code' => $this->countyCode($province['segment'], $segment),
            'source_id' => $sourceId,
            'province_code' => $province['record']['code'],
            'province_source_id' => $province['record']['source_id'],
            'name_fa' => $name,
            'name_en' => null,
            'slug' => $this->slug($name, "county-{$sourceId}", $usedSlugs['counties']),
            'normalized_name' => $this->normalizer->search($name),
            'display_name_fa' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => self::SOURCE_VERSION,
            'data_version' => self::DATA_VERSION,
        ];
        $key = $province['key'].'|tehran-source-county-'.$sourceId;
        $meta = [
            'key' => $key,
            'province_key' => $province['key'],
            'segment' => $segment,
            'record' => $record,
            'name_key' => $this->nameKey($name),
        ];

        $hierarchy['counties'][] = $record;
        $hierarchy['county_meta'][$key] = $meta;

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $province
     * @param  array<string, mixed>  $county
     * @param  array<string, mixed>  $hierarchy
     * @param  array<string, array<string, true>>  $usedSlugs
     * @return array<string, mixed>
     */
    private function addOfficialDistrict(string $name, array $province, array $county, array &$hierarchy, array &$usedSlugs): array
    {
        $hierarchy['official_sequence_by_county'][$county['key']] = ($hierarchy['official_sequence_by_county'][$county['key']] ?? 0) + 1;
        $sourceId = count($hierarchy['official_districts']) + 1;
        $segment = $hierarchy['official_sequence_by_county'][$county['key']];
        $record = [
            'code' => $this->officialDistrictCode($province['segment'], $county['segment'], $segment),
            'source_id' => $sourceId,
            'province_code' => $province['record']['code'],
            'county_code' => $county['record']['code'],
            'county_source_id' => $county['record']['source_id'],
            'name_fa' => $name,
            'name_en' => null,
            'slug' => $this->slug($name, "official-district-{$sourceId}", $usedSlugs['official_districts']),
            'normalized_name' => $this->normalizer->search($name),
            'display_name_fa' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => self::SOURCE_VERSION,
            'data_version' => self::DATA_VERSION,
        ];
        $key = $county['key'].'|tehran-source-official-district-'.$sourceId;
        $meta = [
            'key' => $key,
            'province_key' => $province['key'],
            'county_key' => $county['key'],
            'segment' => $segment,
            'record' => $record,
            'name_key' => $this->nameKey($name),
        ];

        $hierarchy['official_districts'][] = $record;
        $hierarchy['official_district_meta'][$key] = $meta;

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $province
     * @param  array<string, mixed>  $county
     * @param  array<string, mixed>  $officialDistrict
     * @param  array<string, mixed>  $hierarchy
     * @param  array<string, array<string, true>>  $usedSlugs
     */
    private function addRuralDistrict(string $name, array $province, array $county, array $officialDistrict, array &$hierarchy, array &$usedSlugs): void
    {
        $hierarchy['rural_sequence_by_official_district'][$officialDistrict['key']] = ($hierarchy['rural_sequence_by_official_district'][$officialDistrict['key']] ?? 0) + 1;
        $sourceId = count($hierarchy['rural_districts']) + 1;
        $segment = $hierarchy['rural_sequence_by_official_district'][$officialDistrict['key']];

        $hierarchy['rural_districts'][] = [
            'code' => $this->ruralDistrictCode($province['segment'], $county['segment'], $officialDistrict['segment'], $segment),
            'source_id' => $sourceId,
            'province_code' => $province['record']['code'],
            'county_code' => $county['record']['code'],
            'official_district_code' => $officialDistrict['record']['code'],
            'name_fa' => $name,
            'name_en' => null,
            'slug' => $this->slug($name, "rural-district-{$sourceId}", $usedSlugs['rural_districts']),
            'normalized_name' => $this->normalizer->search($name),
            'display_name_fa' => null,
            'is_active' => true,
            'source' => 'package',
            'source_version' => self::SOURCE_VERSION,
            'data_version' => self::DATA_VERSION,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function provinceKey(array $row): string
    {
        $sourceCode = $this->stringValue($row['province_source_code'] ?? null);

        return $sourceCode !== '' ? 'source:'.$sourceCode : 'name:'.$this->nameKey($row['province_name']);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function countyKey(array $row): string
    {
        $sourceCode = $this->stringValue($row['county_source_code'] ?? null);

        return $row['province_key'].'|'.($sourceCode !== '' ? $sourceCode : $this->nameKey($row['county_name']));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function officialDistrictKey(array $row): string
    {
        $sourceCode = $this->stringValue($row['official_district_source_code'] ?? null);

        return $this->countyKey($row).'|'.($sourceCode !== '' ? $sourceCode : $this->nameKey($row['official_district_name']));
    }

    private function provinceCode(int $provinceSegment): string
    {
        return $this->codes->province($provinceSegment);
    }

    private function countyCode(int $provinceSegment, int $countySegment): string
    {
        return $this->codes->county($this->provinceCode($provinceSegment), $countySegment);
    }

    private function officialDistrictCode(int $provinceSegment, int $countySegment, int $officialDistrictSegment): string
    {
        return $this->codes->officialDistrict($this->countyCode($provinceSegment, $countySegment), $officialDistrictSegment);
    }

    private function cityCode(int $provinceSegment, int $countySegment, int $officialDistrictSegment, int $citySegment): string
    {
        return $this->codes->city($this->officialDistrictCode($provinceSegment, $countySegment, $officialDistrictSegment), $citySegment);
    }

    private function ruralDistrictCode(int $provinceSegment, int $countySegment, int $officialDistrictSegment, int $ruralDistrictSegment): string
    {
        return $this->codes->ruralDistrict($this->officialDistrictCode($provinceSegment, $countySegment, $officialDistrictSegment), $ruralDistrictSegment);
    }

    private function cityRegionCode(string $cityCode, int $regionNumber): string
    {
        return $this->codes->cityRegion($cityCode, $regionNumber);
    }

    private function neighborhoodCode(string $cityCode, string $cityRegionCode, int $regionRowNumber): string
    {
        return $this->codes->neighborhood($cityCode, $cityRegionCode, $regionRowNumber);
    }

    /**
     * @param  array<string, true>  $used
     */
    private function slug(string $name, string $fallback, array &$used): string
    {
        $slug = $this->normalizer->slug($name);

        if ($slug === '') {
            $slug = Str::slug($fallback);
        }

        if ($slug === '') {
            $slug = $fallback;
        }

        $candidate = $slug;
        $suffix = 2;

        while (isset($used[$candidate])) {
            $candidate = $slug.'-'.$suffix++;
        }

        $used[$candidate] = true;

        return $candidate;
    }

    private function sameName(string $first, string $second): bool
    {
        return $this->nameKey($first) === $this->nameKey($second);
    }

    private function nameKey(string $name): string
    {
        return $this->normalizer->search($name);
    }

    /**
     * @return array<int, string>
     */
    private function splitSourceList(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s*-\s*/u', $value) ?: [];
        $items = [];

        foreach ($parts as $part) {
            $part = $this->persianDisplayText(trim($part));

            if ($part !== '') {
                $items[] = $part;
            }
        }

        return $items;
    }

    private function startsWith(string $value, string $prefix): bool
    {
        return str_starts_with($value, $prefix);
    }

    private function stripLeadingLabel(string $value, string $label): string
    {
        $value = preg_replace('/^'.preg_quote($label, '/').'\s*/u', '', $value) ?? $value;

        return trim($value);
    }

    private function intValue(mixed $value, int $fallback): int
    {
        return is_numeric($value) ? (int) $value : $fallback;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? trim((string) $value) : '';
    }

    private function nameValue(mixed $value): string
    {
        return $this->persianDisplayText($this->stringValue($value));
    }

    private function persianDisplayText(string $value): string
    {
        return strtr($value, [
            'ك' => 'ک',
            'ي' => 'ی',
            'ى' => 'ی',
        ]);
    }

    private function neighborhoodType(string $name): string
    {
        return match (true) {
            str_contains($name, 'خیابان') => 'street',
            str_contains($name, 'بلوار') => 'boulevard',
            str_contains($name, 'میدان') => 'square',
            str_contains($name, 'بزرگراه') || str_contains($name, 'اتوبان') => 'highway',
            str_contains($name, 'پارک') => 'park',
            str_contains($name, 'منطقه') || str_contains($name, 'ناحیه') => 'area',
            default => 'neighborhood',
        };
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     */
    private function writeDatasets(string $outputPath, array $datasets): void
    {
        if (! is_dir($outputPath) && ! mkdir($outputPath, 0775, true) && ! is_dir($outputPath)) {
            throw new RuntimeException("Unable to create output directory [{$outputPath}].");
        }

        foreach ($datasets as $dataset => $records) {
            $this->writeJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor($dataset), $records);
        }
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     * @return array<string, mixed>
     */
    private function manifestFor(array $datasets): array
    {
        $counts = [];

        foreach (LocationDataManifest::datasets() as $dataset) {
            $counts[$dataset] = count($datasets[$dataset] ?? []);
        }

        return [
            'data_version' => self::DATA_VERSION,
            'country_code' => 'IR',
            'source' => [
                'name' => 'excel-import',
                'version' => self::SOURCE_VERSION,
                'files' => [
                    self::NATIONWIDE_FILE,
                    self::TEHRAN_OFFICIAL_FILE,
                    self::TEHRAN_MUNICIPAL_FILE,
                ],
            ],
            'contains' => [
                'provinces' => $counts['provinces'] > 0,
                'counties' => $counts['counties'] > 0,
                'official_districts' => $counts['official_districts'] > 0,
                'rural_districts' => $counts['rural_districts'] > 0,
                'cities' => $counts['cities'] > 0,
                'city_regions' => $counts['city_regions'] > 0,
                'city_areas' => $counts['city_areas'] > 0,
                'neighborhoods' => $counts['neighborhoods'] > 0,
                'neighborhood_region' => $counts['neighborhood_region'] > 0,
                'aliases' => $counts['aliases'] > 0,
            ],
            'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'code_scheme' => LocationCodeGenerator::scheme(),
            'counts' => $counts,
            'checksum' => $this->checksum($datasets),
        ];
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     */
    private function checksum(array $datasets): string
    {
        $payload = [];

        foreach (LocationDataManifest::datasets() as $dataset) {
            $payload[$dataset] = $datasets[$dataset] ?? [];
        }

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>|array<int, array<string, mixed>>  $data
     */
    private function writeJson(string $path, array $data): void
    {
        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readXlsxRows(string $path, string $sheetName): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException("Unable to open XLSX file [{$path}].");
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheetPath = $this->sheetPath($zip, $sheetName);
            $xml = $zip->getFromName($sheetPath);

            if (! is_string($xml)) {
                throw new RuntimeException("Worksheet [{$sheetName}] was not found in [{$path}].");
            }

            return $this->rowsFromSheetXml($xml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if (! is_string($xml)) {
            return [];
        }

        $dom = $this->dom($xml);
        $xpath = new DOMXPath($dom);
        $strings = [];

        foreach ($xpath->query('//*[local-name()="si"]') ?: [] as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            $parts = [];

            foreach ($xpath->query('.//*[local-name()="t"]', $item) ?: [] as $text) {
                $parts[] = $text->textContent;
            }

            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function sheetPath(ZipArchive $zip, string $sheetName): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if (! is_string($workbookXml) || ! is_string($relationshipsXml)) {
            throw new RuntimeException('XLSX workbook metadata is incomplete.');
        }

        $relationships = [];
        $relationshipDom = $this->dom($relationshipsXml);
        $relationshipXpath = new DOMXPath($relationshipDom);

        foreach ($relationshipXpath->query('//*[local-name()="Relationship"]') ?: [] as $relationship) {
            if (! $relationship instanceof DOMElement) {
                continue;
            }

            $relationships[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
        }

        $workbookDom = $this->dom($workbookXml);
        $workbookXpath = new DOMXPath($workbookDom);
        $fallbackRelationshipId = null;

        foreach ($workbookXpath->query('//*[local-name()="sheet"]') ?: [] as $sheet) {
            if (! $sheet instanceof DOMElement) {
                continue;
            }

            $relationshipId = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')
                ?: $sheet->getAttribute('r:id');

            $fallbackRelationshipId ??= $relationshipId;

            if ($sheet->getAttribute('name') === $sheetName) {
                return $this->normalizeXlsxPath($relationships[$relationshipId] ?? '');
            }
        }

        if ($fallbackRelationshipId !== null && isset($relationships[$fallbackRelationshipId])) {
            return $this->normalizeXlsxPath($relationships[$fallbackRelationshipId]);
        }

        throw new RuntimeException("Worksheet [{$sheetName}] was not found.");
    }

    private function normalizeXlsxPath(string $target): string
    {
        $target = ltrim($target, '/');

        return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<int, mixed>>
     */
    private function rowsFromSheetXml(string $xml, array $sharedStrings): array
    {
        $dom = $this->dom($xml);
        $xpath = new DOMXPath($dom);
        $rows = [];

        foreach ($xpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $rowNode) {
            if (! $rowNode instanceof DOMElement) {
                continue;
            }

            $row = [];
            $maxColumn = -1;

            foreach ($xpath->query('./*[local-name()="c"]', $rowNode) ?: [] as $cellNode) {
                if (! $cellNode instanceof DOMElement) {
                    continue;
                }

                $column = $this->columnIndex($cellNode->getAttribute('r'));
                $row[$column] = $this->cellValue($cellNode, $sharedStrings);
                $maxColumn = max($maxColumn, $column);
            }

            if ($maxColumn < 0) {
                $rows[] = [];

                continue;
            }

            $filled = [];

            for ($column = 0; $column <= $maxColumn; $column++) {
                $filled[$column] = $row[$column] ?? null;
            }

            $rows[] = $filled;
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private function cellValue(DOMElement $cell, array $sharedStrings): mixed
    {
        $type = $cell->getAttribute('t');
        $xpath = new DOMXPath($cell->ownerDocument);

        if ($type === 'inlineStr') {
            $parts = [];

            foreach ($xpath->query('.//*[local-name()="is"]//*[local-name()="t"]', $cell) ?: [] as $text) {
                $parts[] = $text->textContent;
            }

            return implode('', $parts);
        }

        $valueNodes = $xpath->query('./*[local-name()="v"]', $cell);
        $valueNode = $valueNodes === false ? null : $valueNodes->item(0);
        $value = $valueNode?->textContent;

        if ($value === null) {
            return null;
        }

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'b') {
            return $value === '1';
        }

        return $value;
    }

    private function columnIndex(string $reference): int
    {
        if (preg_match('/^([A-Z]+)/i', $reference, $matches) !== 1) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;

        for ($position = 0, $length = strlen($letters); $position < $length; $position++) {
            $index = ($index * 26) + (ord($letters[$position]) - 64);
        }

        return $index - 1;
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument;

        try {
            $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to parse XLSX XML: '.$exception->getMessage(), previous: $exception);
        }

        return $dom;
    }
}
