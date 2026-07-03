<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Data;

use Throwable;

class LocationDataValidator
{
    public function __construct(
        private readonly JsonLocationDataRepository $repository,
    ) {}

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
        $this->validateManifestCounts($manifest, $datasets, $errors, $checks);

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
            'cities' => ['code', 'source_id', 'province_code', 'province_source_id', 'name_fa', 'name_en', 'slug', 'normalized_name', 'display_name_fa', 'is_province_capital', 'latitude', 'longitude', 'is_active', 'source', 'source_version', 'data_version'],
            'city_regions' => ['code', 'source_id', 'city_code', 'city_source_id', 'name_fa', 'slug', 'normalized_name', 'is_active', 'source', 'source_version', 'data_version'],
            'city_areas' => ['code', 'source_id', 'city_region_code', 'city_region_source_id', 'name_fa', 'slug', 'normalized_name', 'is_active', 'source', 'source_version', 'data_version'],
            'neighborhoods' => ['code', 'source_id', 'city_code', 'city_source_id', 'name_fa', 'name_en', 'slug', 'normalized_name', 'display_name_fa', 'type', 'latitude', 'longitude', 'is_active', 'source', 'source_version', 'data_version'],
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
        $provinceCodes = $this->codes($datasets['provinces'] ?? []);
        $cityCodes = $this->codes($datasets['cities'] ?? []);

        foreach ($datasets['cities'] ?? [] as $index => $city) {
            $provinceCode = $city['province_code'] ?? null;

            if (! is_string($provinceCode) || ! isset($provinceCodes[$provinceCode])) {
                $errors[] = "Dataset [cities] record [{$index}] references missing province_code [{$provinceCode}].";
            }
        }

        foreach ($datasets['neighborhoods'] ?? [] as $index => $neighborhood) {
            $cityCode = $neighborhood['city_code'] ?? null;

            if (! is_string($cityCode) || ! isset($cityCodes[$cityCode])) {
                $errors[] = "Dataset [neighborhoods] record [{$index}] references missing city_code [{$cityCode}].";
            }
        }

        $checks[] = 'Dataset foreign references were checked.';
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, true>
     */
    private function codes(array $records): array
    {
        $codes = [];

        foreach ($records as $record) {
            $code = $record['code'] ?? null;

            if (is_string($code) && $code !== '') {
                $codes[$code] = true;
            }
        }

        return $codes;
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
