<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Data;

use InvalidArgumentException;
use Throwable;
use Zarbin\IranLocations\Coding\LocationCodeGenerator;
use Zarbin\IranLocations\Support\LocationModelResolver;

class LocationDataValidator
{
    private const LEGACY_CODE_PREFIX = 'i'.'r.';

    /**
     * @var array<string, true>
     */
    private const CODE_DATASETS = [
        'provinces' => true,
        'counties' => true,
        'official_districts' => true,
        'rural_districts' => true,
        'cities' => true,
        'city_regions' => true,
        'city_areas' => true,
        'neighborhoods' => true,
    ];

    private const FORBIDDEN_CODE_PARTS = [
        'province',
        'county',
        'official',
        'district',
        'rural',
        'city',
        'region',
        'area',
        'neighborhood',
        'tehran',
    ];

    private readonly LocationCodeGenerator $codes;

    public function __construct(
        private readonly JsonLocationDataRepository $repository,
        ?LocationCodeGenerator $codes = null,
    ) {
        $this->codes = $codes ?? new LocationCodeGenerator;
    }

    /**
     * @return array{ok: bool, errors: array<int, string>, checks: array<int, string>}
     */
    public function validate(): array
    {
        $errors = [];
        $checks = [];
        $path = $this->repository->path();

        $this->requireFile($path, LocationDataManifest::MANIFEST_FILE, $errors, $checks);

        foreach (LocationDataManifest::datasetFiles() as $file) {
            $this->requireFile($path, $file, $errors, $checks);
        }

        if (is_file($path.DIRECTORY_SEPARATOR.'districts.json')) {
            $errors[] = 'Public dataset [districts.json] is not allowed.';
        } else {
            $checks[] = 'No public districts dataset was found.';
        }

        try {
            $manifest = $this->repository->manifest();
            $datasets = $this->datasets();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();

            return $this->result($errors, $checks);
        }

        $this->validateRecords($datasets, $errors, $checks);
        $this->validateReferences($datasets, $errors, $checks);
        $this->validateStructuralCodeConsistency($datasets, $errors, $checks);
        $this->validateManifestGeneratedAt($manifest, $errors, $checks);
        $this->validateManifestCodeScheme($manifest, $errors, $checks);
        $this->validateManifestCounts($manifest, $datasets, $errors, $checks);
        $this->validateManifestChecksum($manifest, $datasets, $errors, $checks);

        return $this->result($errors, $checks);
    }

    /**
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function requireFile(string $path, string $file, array &$errors, array &$checks): void
    {
        if (! is_file($path.DIRECTORY_SEPARATOR.$file)) {
            $errors[] = "Required data file [{$file}] is missing.";

            return;
        }

        $checks[] = "Data file [{$file}] exists.";
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function datasets(): array
    {
        $datasets = [];

        foreach (LocationDataManifest::datasets() as $dataset) {
            $datasets[$dataset] = $this->repository->all($dataset);
        }

        return $datasets;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateRecords(array $datasets, array &$errors, array &$checks): void
    {
        foreach ($datasets as $dataset => $records) {
            $this->validateUniqueCodes($dataset, $records, $errors, $checks);
            $this->validateRequiredFields($dataset, $records, $errors, $checks);
            $this->validateNormalizedNames($dataset, $records, $errors, $checks);
            $this->validateCodeFormats($dataset, $records, $errors, $checks);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateUniqueCodes(string $dataset, array $records, array &$errors, array &$checks): void
    {
        $codes = [];

        if ($dataset === 'neighborhood_region') {
            $pairs = [];

            foreach ($records as $index => $record) {
                $neighborhoodCode = $record['neighborhood_code'] ?? null;
                $cityRegionCode = $record['city_region_code'] ?? null;

                if (! is_string($neighborhoodCode) || ! is_string($cityRegionCode)) {
                    continue;
                }

                $pair = $neighborhoodCode.'|'.$cityRegionCode;

                if (isset($pairs[$pair])) {
                    $errors[] = "Dataset [neighborhood_region] contains duplicate pair [{$pair}].";
                }

                $pairs[$pair] = true;
            }

            $checks[] = 'Dataset [neighborhood_region] has unique pairs.';

            return;
        }

        if ($dataset === 'aliases') {
            $aliases = [];

            foreach ($records as $index => $record) {
                $locationType = $record['location_type'] ?? null;
                $locationCode = $record['location_code'] ?? null;
                $normalizedAlias = $record['normalized_alias'] ?? null;

                if (! is_string($locationType) || ! is_string($locationCode) || ! is_string($normalizedAlias)) {
                    continue;
                }

                try {
                    $key = LocationModelResolver::normalizeLocationType($locationType).'|'.$locationCode.'|'.$normalizedAlias;
                } catch (InvalidArgumentException) {
                    continue;
                }

                if (isset($aliases[$key])) {
                    $errors[] = "Dataset [aliases] contains duplicate alias target [{$key}].";
                }

                $aliases[$key] = true;
            }

            $checks[] = 'Dataset [aliases] has unique target aliases.';

            return;
        }

        foreach ($records as $index => $record) {
            $code = $record['code'] ?? null;

            if (! is_string($code) || $code === '') {
                if ($dataset !== 'aliases') {
                    $errors[] = "Dataset [{$dataset}] record [{$index}] is missing required field [code].";
                }

                continue;
            }

            if (isset($codes[$code])) {
                $errors[] = "Dataset [{$dataset}] contains duplicate code [{$code}].";
            }

            $codes[$code] = true;
        }

        $checks[] = "Dataset [{$dataset}] has unique codes.";
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateCodeFormats(string $dataset, array $records, array &$errors, array &$checks): void
    {
        if ($dataset === 'neighborhood_region') {
            foreach ($records as $index => $record) {
                $this->validateCodeValue('neighborhoods', $dataset, $index, 'neighborhood_code', $record['neighborhood_code'] ?? null, $errors);
                $this->validateCodeValue('city_regions', $dataset, $index, 'city_region_code', $record['city_region_code'] ?? null, $errors);
            }

            $checks[] = 'Dataset [neighborhood_region] reference codes match package code scheme.';

            return;
        }

        if ($dataset === 'aliases') {
            foreach ($records as $index => $record) {
                $locationType = $record['location_type'] ?? null;

                if (! is_string($locationType) || blank($locationType)) {
                    continue;
                }

                try {
                    $targetDataset = LocationModelResolver::datasetForLocationType($locationType);
                } catch (InvalidArgumentException) {
                    continue;
                }

                $this->validateCodeValue($targetDataset, $dataset, $index, 'location_code', $record['location_code'] ?? null, $errors);
            }

            $checks[] = 'Dataset [aliases] target codes match package code scheme.';

            return;
        }

        if (! isset(self::CODE_DATASETS[$dataset])) {
            return;
        }

        foreach ($records as $index => $record) {
            $this->validateCodeValue($dataset, $dataset, $index, 'code', $record['code'] ?? null, $errors);
        }

        $checks[] = "Dataset [{$dataset}] codes match package code scheme.";
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function validateCodeValue(string $expectedDataset, string $dataset, int $index, string $field, mixed $code, array &$errors): void
    {
        if (! is_string($code) || $code === '') {
            return;
        }

        if ($code !== strtolower($code)) {
            $errors[] = "Dataset [{$dataset}] record [{$index}] field [{$field}] code [{$code}] must be lowercase.";
        }

        if (str_starts_with($code, self::LEGACY_CODE_PREFIX)) {
            $errors[] = "Dataset [{$dataset}] record [{$index}] field [{$field}] code [{$code}] must not start with [".self::LEGACY_CODE_PREFIX.'].';
        }

        foreach (self::FORBIDDEN_CODE_PARTS as $part) {
            if (str_contains($code, $part)) {
                $errors[] = "Dataset [{$dataset}] record [{$index}] field [{$field}] code [{$code}] must not contain [{$part}].";
            }
        }

        if (! $this->codes->matchesDataset($expectedDataset, $code)) {
            $errors[] = "Dataset [{$dataset}] record [{$index}] field [{$field}] code [{$code}] does not match package code scheme for [{$expectedDataset}].";
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateRequiredFields(string $dataset, array $records, array &$errors, array &$checks): void
    {
        $required = $this->requiredFields($dataset);

        foreach ($records as $index => $record) {
            foreach ($required as $field) {
                if (! array_key_exists($field, $record)) {
                    $errors[] = "Dataset [{$dataset}] record [{$index}] is missing required field [{$field}].";
                }
            }

            if ($dataset === 'aliases' && blank($record['normalized_alias'] ?? null)) {
                $errors[] = "Dataset [aliases] record [{$index}] is missing required field [normalized_alias].";
            }
        }

        $checks[] = "Dataset [{$dataset}] required fields were checked.";
    }

    /**
     * @return array<int, string>
     */
    private function requiredFields(string $dataset): array
    {
        return match ($dataset) {
            'provinces' => ['code', 'source_id', 'name_fa', 'name_en', 'slug', 'normalized_name', 'display_name_fa', 'is_active', 'source', 'source_version', 'data_version'],
            'counties' => ['code', 'source_id', 'province_code', 'province_source_id', 'name_fa', 'name_en', 'slug', 'normalized_name', 'display_name_fa', 'is_active', 'source', 'source_version', 'data_version'],
            'official_districts' => ['code', 'source_id', 'province_code', 'county_code', 'county_source_id', 'name_fa', 'name_en', 'slug', 'normalized_name', 'display_name_fa', 'is_active', 'source', 'source_version', 'data_version'],
            'rural_districts' => ['code', 'source_id', 'province_code', 'county_code', 'official_district_code', 'name_fa', 'name_en', 'slug', 'normalized_name', 'display_name_fa', 'is_active', 'source', 'source_version', 'data_version'],
            'cities' => ['code', 'source_id', 'province_code', 'province_source_id', 'county_code', 'county_source_id', 'official_district_code', 'official_district_source_id', 'name_fa', 'name_en', 'slug', 'normalized_name', 'display_name_fa', 'is_province_capital', 'latitude', 'longitude', 'is_active', 'source', 'source_version', 'data_version'],
            'city_regions' => ['code', 'source_id', 'city_code', 'city_source_id', 'name_fa', 'slug', 'normalized_name', 'is_active', 'source', 'source_version', 'data_version'],
            'city_areas' => ['code', 'source_id', 'city_region_code', 'city_region_source_id', 'name_fa', 'slug', 'normalized_name', 'is_active', 'source', 'source_version', 'data_version'],
            'neighborhoods' => ['code', 'source_id', 'city_code', 'city_source_id', 'name_fa', 'name_en', 'slug', 'normalized_name', 'display_name_fa', 'type', 'latitude', 'longitude', 'is_active', 'source', 'source_version', 'data_version'],
            'neighborhood_region' => ['neighborhood_code', 'city_region_code', 'is_primary', 'source'],
            'aliases' => ['location_type', 'location_code', 'alias', 'normalized_alias', 'source'],
            default => [],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateNormalizedNames(string $dataset, array $records, array &$errors, array &$checks): void
    {
        foreach ($records as $index => $record) {
            if (blank($record['name_fa'] ?? null)) {
                continue;
            }

            if (blank($record['normalized_name'] ?? null)) {
                $errors[] = "Dataset [{$dataset}] record [{$index}] has [name_fa] but empty [normalized_name].";
            }
        }

        $checks[] = "Dataset [{$dataset}] normalized names were checked.";
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateReferences(array $datasets, array &$errors, array &$checks): void
    {
        $provincesByCode = $this->recordsByCode($datasets['provinces'] ?? []);
        $countiesByCode = $this->recordsByCode($datasets['counties'] ?? []);
        $officialDistrictsByCode = $this->recordsByCode($datasets['official_districts'] ?? []);
        $ruralDistrictsByCode = $this->recordsByCode($datasets['rural_districts'] ?? []);
        $citiesByCode = $this->recordsByCode($datasets['cities'] ?? []);
        $cityRegionsByCode = $this->recordsByCode($datasets['city_regions'] ?? []);
        $cityAreasByCode = $this->recordsByCode($datasets['city_areas'] ?? []);
        $neighborhoodsByCode = $this->recordsByCode($datasets['neighborhoods'] ?? []);
        $provinceCodes = $this->codeMap($provincesByCode);
        $countyCodes = $this->codeMap($countiesByCode);
        $officialDistrictCodes = $this->codeMap($officialDistrictsByCode);
        $ruralDistrictCodes = $this->codeMap($ruralDistrictsByCode);
        $cityCodes = $this->codeMap($citiesByCode);
        $cityRegionCodes = $this->codeMap($cityRegionsByCode);
        $cityAreaCodes = $this->codeMap($cityAreasByCode);
        $neighborhoodCodes = $this->codeMap($neighborhoodsByCode);
        $codesByDataset = [
            'provinces' => $provinceCodes,
            'counties' => $countyCodes,
            'official_districts' => $officialDistrictCodes,
            'rural_districts' => $ruralDistrictCodes,
            'cities' => $cityCodes,
            'city_regions' => $cityRegionCodes,
            'city_areas' => $cityAreaCodes,
            'neighborhoods' => $neighborhoodCodes,
        ];

        foreach ($datasets['counties'] ?? [] as $index => $county) {
            $provinceCode = $county['province_code'] ?? null;

            if (! is_string($provinceCode) || ! isset($provinceCodes[$provinceCode])) {
                $errors[] = "Dataset [counties] record [{$index}] references missing province_code [{$provinceCode}].";
            }

            $province = is_string($provinceCode) ? ($provincesByCode[$provinceCode] ?? null) : null;

            if ($province !== null && ! $this->sameOptionalString($county['province_source_id'] ?? null, $province['source_id'] ?? null)) {
                $errors[] = "Dataset [counties] record [{$index}] has province_source_id that does not match province_code [{$provinceCode}].";
            }
        }

        foreach ($datasets['official_districts'] ?? [] as $index => $district) {
            $provinceCode = $district['province_code'] ?? null;
            $countyCode = $district['county_code'] ?? null;

            if (! is_string($provinceCode) || ! isset($provinceCodes[$provinceCode])) {
                $errors[] = "Dataset [official_districts] record [{$index}] references missing province_code [{$provinceCode}].";
            }

            if (! is_string($countyCode) || ! isset($countyCodes[$countyCode])) {
                $errors[] = "Dataset [official_districts] record [{$index}] references missing county_code [{$countyCode}].";
            }

            $county = is_string($countyCode) ? ($countiesByCode[$countyCode] ?? null) : null;

            if ($county !== null && is_string($provinceCode) && (string) ($county['province_code'] ?? '') !== $provinceCode) {
                $errors[] = "Dataset [official_districts] record [{$index}] references county_code [{$countyCode}] outside province_code [{$provinceCode}].";
            }
        }

        foreach ($datasets['rural_districts'] ?? [] as $index => $district) {
            $provinceCode = $district['province_code'] ?? null;
            $countyCode = $district['county_code'] ?? null;
            $officialDistrictCode = $district['official_district_code'] ?? null;

            if (! is_string($provinceCode) || ! isset($provinceCodes[$provinceCode])) {
                $errors[] = "Dataset [rural_districts] record [{$index}] references missing province_code [{$provinceCode}].";
            }

            if (! is_string($countyCode) || ! isset($countyCodes[$countyCode])) {
                $errors[] = "Dataset [rural_districts] record [{$index}] references missing county_code [{$countyCode}].";
            }

            if (! is_string($officialDistrictCode) || ! isset($officialDistrictCodes[$officialDistrictCode])) {
                $errors[] = "Dataset [rural_districts] record [{$index}] references missing official_district_code [{$officialDistrictCode}].";
            }

            $county = is_string($countyCode) ? ($countiesByCode[$countyCode] ?? null) : null;
            $officialDistrict = is_string($officialDistrictCode) ? ($officialDistrictsByCode[$officialDistrictCode] ?? null) : null;

            if ($county !== null && is_string($provinceCode) && (string) ($county['province_code'] ?? '') !== $provinceCode) {
                $errors[] = "Dataset [rural_districts] record [{$index}] references county_code [{$countyCode}] outside province_code [{$provinceCode}].";
            }

            if ($officialDistrict !== null && (
                (is_string($provinceCode) && (string) ($officialDistrict['province_code'] ?? '') !== $provinceCode)
                || (is_string($countyCode) && (string) ($officialDistrict['county_code'] ?? '') !== $countyCode)
            )) {
                $errors[] = "Dataset [rural_districts] record [{$index}] references official_district_code [{$officialDistrictCode}] outside province/county chain.";
            }
        }

        foreach ($datasets['cities'] ?? [] as $index => $city) {
            $provinceCode = $city['province_code'] ?? null;
            $countyCode = $city['county_code'] ?? null;
            $officialDistrictCode = $city['official_district_code'] ?? null;

            if (! is_string($provinceCode) || ! isset($provinceCodes[$provinceCode])) {
                $errors[] = "Dataset [cities] record [{$index}] references missing province_code [{$provinceCode}].";
            }

            if (! blank($countyCode) && (! is_string($countyCode) || ! isset($countyCodes[$countyCode]))) {
                $errors[] = "Dataset [cities] record [{$index}] references missing county_code [{$countyCode}].";
            }

            if (! blank($officialDistrictCode) && (! is_string($officialDistrictCode) || ! isset($officialDistrictCodes[$officialDistrictCode]))) {
                $errors[] = "Dataset [cities] record [{$index}] references missing official_district_code [{$officialDistrictCode}].";
            }

            $county = is_string($countyCode) ? ($countiesByCode[$countyCode] ?? null) : null;
            $officialDistrict = is_string($officialDistrictCode) ? ($officialDistrictsByCode[$officialDistrictCode] ?? null) : null;

            if ($county !== null && is_string($provinceCode) && (string) ($county['province_code'] ?? '') !== $provinceCode) {
                $errors[] = "Dataset [cities] record [{$index}] references county_code [{$countyCode}] outside province_code [{$provinceCode}].";
            }

            if ($officialDistrict !== null && is_string($provinceCode) && (string) ($officialDistrict['province_code'] ?? '') !== $provinceCode) {
                $errors[] = "Dataset [cities] record [{$index}] references official_district_code [{$officialDistrictCode}] outside province_code [{$provinceCode}].";
            }

            if ($officialDistrict !== null && ! blank($countyCode) && is_string($countyCode) && (string) ($officialDistrict['county_code'] ?? '') !== $countyCode) {
                $errors[] = "Dataset [cities] record [{$index}] references official_district_code [{$officialDistrictCode}] outside county_code [{$countyCode}].";
            }
        }

        foreach ($datasets['city_regions'] ?? [] as $index => $region) {
            $cityCode = $region['city_code'] ?? null;

            if (! is_string($cityCode) || ! isset($cityCodes[$cityCode])) {
                $errors[] = "Dataset [city_regions] record [{$index}] references missing city_code [{$cityCode}].";
            }
        }

        foreach ($datasets['city_areas'] ?? [] as $index => $area) {
            $cityRegionCode = $area['city_region_code'] ?? null;
            $cityCode = $this->firstString($area, ['city_code']);

            if (! is_string($cityRegionCode) || ! isset($cityRegionCodes[$cityRegionCode])) {
                $errors[] = "Dataset [city_areas] record [{$index}] references missing city_region_code [{$cityRegionCode}].";
            }

            $region = is_string($cityRegionCode) ? ($cityRegionsByCode[$cityRegionCode] ?? null) : null;

            if ($region !== null && $cityCode !== null && (string) ($region['city_code'] ?? '') !== $cityCode) {
                $errors[] = "Dataset [city_areas] record [{$index}] references city_region_code [{$cityRegionCode}] outside city_code [{$cityCode}].";
            }
        }

        foreach ($datasets['neighborhoods'] ?? [] as $index => $neighborhood) {
            $cityCode = $neighborhood['city_code'] ?? null;

            if (! is_string($cityCode) || ! isset($cityCodes[$cityCode])) {
                $errors[] = "Dataset [neighborhoods] record [{$index}] references missing city_code [{$cityCode}].";
            }

            $cityRegionCode = $this->firstString($neighborhood, ['default_city_region_code', 'city_region_code', 'region_code']);
            $cityAreaCode = $this->firstString($neighborhood, ['default_city_area_code', 'city_area_code', 'area_code']);
            $region = $cityRegionCode === null ? null : ($cityRegionsByCode[$cityRegionCode] ?? null);
            $area = $cityAreaCode === null ? null : ($cityAreasByCode[$cityAreaCode] ?? null);

            if ($cityRegionCode !== null && $region === null) {
                $errors[] = "Dataset [neighborhoods] record [{$index}] references missing city_region_code [{$cityRegionCode}].";
            }

            if ($cityAreaCode !== null && $area === null) {
                $errors[] = "Dataset [neighborhoods] record [{$index}] references missing city_area_code [{$cityAreaCode}].";
            }

            if ($region !== null && is_string($cityCode) && (string) ($region['city_code'] ?? '') !== $cityCode) {
                $errors[] = "Dataset [neighborhoods] record [{$index}] references city_region_code [{$cityRegionCode}] outside city_code [{$cityCode}].";
            }

            if ($area !== null && is_string($cityCode)) {
                $areaRegionCode = $area['city_region_code'] ?? null;
                $areaRegion = is_string($areaRegionCode) ? ($cityRegionsByCode[$areaRegionCode] ?? null) : null;

                if ($areaRegion !== null && (string) ($areaRegion['city_code'] ?? '') !== $cityCode) {
                    $errors[] = "Dataset [neighborhoods] record [{$index}] references city_area_code [{$cityAreaCode}] outside city_code [{$cityCode}].";
                }
            }
        }

        foreach ($datasets['neighborhood_region'] ?? [] as $index => $record) {
            $neighborhoodCode = $record['neighborhood_code'] ?? null;
            $cityRegionCode = $record['city_region_code'] ?? null;

            if (! is_string($neighborhoodCode) || ! isset($neighborhoodCodes[$neighborhoodCode])) {
                $errors[] = "Dataset [neighborhood_region] record [{$index}] references missing neighborhood_code [{$neighborhoodCode}].";
            }

            if (! is_string($cityRegionCode) || ! isset($cityRegionCodes[$cityRegionCode])) {
                $errors[] = "Dataset [neighborhood_region] record [{$index}] references missing city_region_code [{$cityRegionCode}].";
            }

            $neighborhood = is_string($neighborhoodCode) ? ($neighborhoodsByCode[$neighborhoodCode] ?? null) : null;
            $region = is_string($cityRegionCode) ? ($cityRegionsByCode[$cityRegionCode] ?? null) : null;

            if ($neighborhood !== null && $region !== null && (string) ($neighborhood['city_code'] ?? '') !== (string) ($region['city_code'] ?? '')) {
                $errors[] = "Dataset [neighborhood_region] record [{$index}] connects neighborhood_code [{$neighborhoodCode}] to city_region_code [{$cityRegionCode}] in a different city.";
            }
        }

        foreach ($datasets['aliases'] ?? [] as $index => $record) {
            $locationType = $record['location_type'] ?? null;
            $locationCode = $record['location_code'] ?? null;

            if (! is_string($locationType) || blank($locationType)) {
                $errors[] = "Dataset [aliases] record [{$index}] has unsupported location_type [".$this->messageValue($locationType).'].';

                continue;
            }

            try {
                $dataset = LocationModelResolver::datasetForLocationType($locationType);
            } catch (InvalidArgumentException) {
                $errors[] = "Dataset [aliases] record [{$index}] has unsupported location_type [{$locationType}].";

                continue;
            }

            if (! is_string($locationCode) || ! isset($codesByDataset[$dataset][$locationCode])) {
                $errors[] = "Dataset [aliases] record [{$index}] references missing {$dataset} code [{$locationCode}].";
            }
        }

        $checks[] = 'Dataset foreign references were checked.';
        $checks[] = 'Dataset hierarchy consistency was checked.';
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateStructuralCodeConsistency(array $datasets, array &$errors, array &$checks): void
    {
        foreach ($datasets['counties'] ?? [] as $index => $county) {
            $this->assertCodeStartsWith($county['code'] ?? null, $county['province_code'] ?? null, 'counties', $index, 'province_code', $errors);
        }

        foreach ($datasets['official_districts'] ?? [] as $index => $district) {
            $this->assertCodeStartsWith($district['code'] ?? null, $district['province_code'] ?? null, 'official_districts', $index, 'province_code', $errors);
            $this->assertCodeStartsWith($district['code'] ?? null, $district['county_code'] ?? null, 'official_districts', $index, 'county_code', $errors);
        }

        foreach ($datasets['rural_districts'] ?? [] as $index => $district) {
            $this->assertCodeStartsWith($district['code'] ?? null, $district['province_code'] ?? null, 'rural_districts', $index, 'province_code', $errors);
            $this->assertCodeStartsWith($district['code'] ?? null, $district['county_code'] ?? null, 'rural_districts', $index, 'county_code', $errors);
            $this->assertCodeStartsWith($district['code'] ?? null, $district['official_district_code'] ?? null, 'rural_districts', $index, 'official_district_code', $errors);
        }

        foreach ($datasets['cities'] ?? [] as $index => $city) {
            $this->assertCodeStartsWith($city['code'] ?? null, $city['province_code'] ?? null, 'cities', $index, 'province_code', $errors);
            $this->assertCodeStartsWith($city['code'] ?? null, $city['county_code'] ?? null, 'cities', $index, 'county_code', $errors);
            $this->assertCodeStartsWith($city['code'] ?? null, $city['official_district_code'] ?? null, 'cities', $index, 'official_district_code', $errors);
        }

        foreach ($datasets['city_regions'] ?? [] as $index => $region) {
            $this->assertCodeStartsWith($region['code'] ?? null, $region['city_code'] ?? null, 'city_regions', $index, 'city_code', $errors);
        }

        foreach ($datasets['city_areas'] ?? [] as $index => $area) {
            $this->assertCodeStartsWith($area['code'] ?? null, $area['city_region_code'] ?? null, 'city_areas', $index, 'city_region_code', $errors);
        }

        foreach ($datasets['neighborhoods'] ?? [] as $index => $neighborhood) {
            $cityRegionCode = $this->firstString($neighborhood, ['default_city_region_code', 'city_region_code', 'region_code']);

            if ($cityRegionCode === null) {
                $errors[] = "Dataset [neighborhoods] record [{$index}] is missing default_city_region_code required by package code scheme.";
            }

            $this->assertCodeStartsWith($neighborhood['code'] ?? null, $neighborhood['city_code'] ?? null, 'neighborhoods', $index, 'city_code', $errors);
            $this->assertCodeStartsWith($neighborhood['code'] ?? null, $cityRegionCode, 'neighborhoods', $index, 'default_city_region_code', $errors);
        }

        foreach ($datasets['neighborhood_region'] ?? [] as $index => $record) {
            $this->assertCodesShareCityPath($record['neighborhood_code'] ?? null, $record['city_region_code'] ?? null, 'neighborhood_region', $index, $errors);
        }

        $checks[] = 'Dataset code hierarchy matches package code scheme.';
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function assertCodeStartsWith(mixed $childCode, mixed $parentCode, string $dataset, int $index, string $parentField, array &$errors): void
    {
        if (! is_string($childCode) || ! is_string($parentCode) || $childCode === '' || $parentCode === '') {
            return;
        }

        try {
            $childPath = $this->codes->path($childCode);
            $parentPath = $this->codes->path($parentCode);
        } catch (InvalidArgumentException) {
            return;
        }

        if (! $this->codes->startsWithPath($childPath, $parentPath)) {
            $errors[] = "Dataset [{$dataset}] record [{$index}] code [{$childCode}] is not under {$parentField} [{$parentCode}].";
        }
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function assertCodesShareCityPath(mixed $neighborhoodCode, mixed $cityRegionCode, string $dataset, int $index, array &$errors): void
    {
        if (! is_string($neighborhoodCode) || ! is_string($cityRegionCode) || $neighborhoodCode === '' || $cityRegionCode === '') {
            return;
        }

        try {
            $neighborhoodPath = $this->codes->path($neighborhoodCode);
            $regionPath = $this->codes->path($cityRegionCode);
        } catch (InvalidArgumentException) {
            return;
        }

        if (array_slice($neighborhoodPath, 0, 4) !== array_slice($regionPath, 0, 4)) {
            $errors[] = "Dataset [{$dataset}] record [{$index}] connects neighborhood_code [{$neighborhoodCode}] to city_region_code [{$cityRegionCode}] in a different code city path.";
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function recordsByCode(array $records): array
    {
        $mapped = [];

        foreach ($records as $record) {
            $code = $record['code'] ?? null;

            if (is_string($code) && $code !== '') {
                $mapped[$code] = $record;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, array<string, mixed>>  $records
     * @return array<string, true>
     */
    private function codeMap(array $records): array
    {
        return array_fill_keys(array_keys($records), true);
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $keys
     */
    private function firstString(array $record, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $record[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function sameOptionalString(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) {
            return true;
        }

        return (string) $left === (string) $right;
    }

    private function messageValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return get_debug_type($value);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateManifestCounts(array $manifest, array $datasets, array &$errors, array &$checks): void
    {
        $counts = $manifest['counts'] ?? null;

        if (! is_array($counts)) {
            $errors[] = 'Manifest is missing [counts].';

            return;
        }

        foreach (LocationDataManifest::datasets() as $dataset) {
            $expected = $counts[$dataset] ?? null;
            $actual = count($datasets[$dataset] ?? []);

            if ($expected !== $actual) {
                $errors[] = "Manifest count for [{$dataset}] is [{$expected}], actual count is [{$actual}].";
            }
        }

        $checks[] = 'Manifest counts match data files.';
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateManifestGeneratedAt(array $manifest, array &$errors, array &$checks): void
    {
        $generatedAt = $manifest['generated_at'] ?? null;

        if (! is_string($generatedAt) || trim($generatedAt) === '') {
            $errors[] = 'Manifest [generated_at] must be a non-empty date-time string.';

            return;
        }

        if (! $this->isUtcGeneratedAtTimestamp($generatedAt)) {
            $errors[] = "Manifest [generated_at] must use UTC timestamp format [YYYY-MM-DDTHH:MM:SSZ]; got [{$generatedAt}].";

            return;
        }

        $checks[] = 'Manifest generated_at is a valid date-time string.';
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateManifestCodeScheme(array $manifest, array &$errors, array &$checks): void
    {
        $scheme = $manifest['code_scheme'] ?? null;

        if (! is_array($scheme)) {
            $errors[] = 'Manifest is missing [code_scheme].';

            return;
        }

        foreach (LocationCodeGenerator::scheme() as $key => $expected) {
            $actual = $scheme[$key] ?? null;

            if ($actual !== $expected) {
                $errors[] = 'Manifest code_scheme ['.$key.'] is ['.$this->messageValue($actual).'], expected ['.$this->messageValue($expected).'].';
            }
        }

        $checks[] = 'Manifest code_scheme matches package code scheme.';
    }

    private function isUtcGeneratedAtTimestamp(string $value): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $value, $matches) !== 1) {
            return false;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];
        $hour = (int) $matches[4];
        $minute = (int) $matches[5];
        $second = (int) $matches[6];

        return checkdate($month, $day, $year)
            && $hour >= 0 && $hour <= 23
            && $minute >= 0 && $minute <= 59
            && $second >= 0 && $second <= 59;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     */
    private function validateManifestChecksum(array $manifest, array $datasets, array &$errors, array &$checks): void
    {
        $expected = $manifest['checksum'] ?? null;

        if (! is_string($expected) || $expected === '') {
            $errors[] = 'Manifest is missing [checksum].';

            return;
        }

        $actual = $this->checksum($datasets);

        if ($expected !== $actual) {
            $errors[] = "Manifest checksum is [{$expected}], actual checksum is [{$actual}].";

            return;
        }

        $checks[] = 'Manifest checksum matches data files.';
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
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $checks
     * @return array{ok: bool, errors: array<int, string>, checks: array<int, string>}
     */
    private function result(array $errors, array $checks): array
    {
        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'checks' => $checks,
        ];
    }
}
