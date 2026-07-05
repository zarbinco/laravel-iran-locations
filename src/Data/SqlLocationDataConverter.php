<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Data;

use Illuminate\Support\Str;
use RuntimeException;
use Zarbin\IranLocations\Contracts\LocationNormalizer;

class SqlLocationDataConverter
{
    private const DATA_VERSION = '0.1.0-dev';

    private const SOURCE_VERSION = 'initial';

    public function __construct(
        private readonly LocationNormalizer $normalizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function convertDirectory(string $sourcePath, string $outputPath): array
    {
        $provinces = $this->readSqlFile($sourcePath, 'provinces.sql');
        $cities = $this->readSqlFile($sourcePath, 'cities.sql');
        $districts = $this->readSqlFile($sourcePath, 'districts.sql');

        return $this->convertRows($provinces, $cities, $districts, $outputPath);
    }

    /**
     * @param  array<int, array<string, mixed>>  $provinceRows
     * @param  array<int, array<string, mixed>>  $cityRows
     * @param  array<int, array<string, mixed>>  $districtRows
     * @return array<string, mixed>
     */
    public function convertRows(array $provinceRows, array $cityRows, array $districtRows, string $outputPath): array
    {
        $skipped = [];
        $missingReferences = [];

        $provinces = $this->convertProvinces($provinceRows, $skipped);
        $cities = $this->convertCities($cityRows, $provinces, $skipped, $missingReferences);
        $neighborhoods = $this->convertNeighborhoods($districtRows, $cities, $skipped, $missingReferences);

        $datasets = [
            'provinces' => $provinces['records'],
            'cities' => $cities['records'],
            'city_regions' => [],
            'city_areas' => [],
            'neighborhoods' => $neighborhoods,
            'aliases' => [],
        ];

        $this->writeDatasets($outputPath, $datasets);
        $manifest = $this->manifestFor($datasets);
        $this->writeJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::MANIFEST_FILE, $manifest);

        return [
            'manifest' => $manifest,
            'counts' => $manifest['counts'],
            'skipped' => $skipped,
            'missing_references' => $missingReferences,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseSql(string $sql): array
    {
        $rows = [];

        foreach ($this->insertStatements($sql) as $statement) {
            $columns = trim($statement['columns']) !== ''
                ? $this->splitColumns($statement['columns'])
                : [];

            foreach ($this->valueGroups($statement['values']) as $values) {
                $row = $columns === []
                    ? $this->rowWithoutColumns($values)
                    : $this->rowWithColumns($columns, $values);

                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $skipped
     * @return array{records: array<int, array<string, mixed>>, by_source_id: array<int, array<string, mixed>>}
     */
    private function convertProvinces(array $rows, array &$skipped): array
    {
        $records = [];
        $bySourceId = [];
        $usedSlugs = [];

        foreach ($rows as $index => $row) {
            $sourceId = $this->intValue($this->firstValue($row, ['id', 'province_id', 'ostan_id', 'source_id', 'column_0']), $index + 1);
            $name = $this->nameValue($this->firstValue($row, ['name_fa', 'name', 'title', 'province', 'ostan', 'column_1']));

            if ($name === '') {
                $skipped[] = "provinces row [{$index}] missing name.";

                continue;
            }

            $code = $this->provinceCode($sourceId);
            $record = [
                'code' => $code,
                'source_id' => $sourceId,
                'name_fa' => $name,
                'name_en' => null,
                'slug' => $this->uniqueSlug($this->slug($name, "province-{$sourceId}"), $usedSlugs, $sourceId),
                'normalized_name' => $this->normalizer->search($name),
                'display_name_fa' => null,
                'is_active' => true,
                'source' => 'package',
                'source_version' => self::SOURCE_VERSION,
                'data_version' => self::DATA_VERSION,
            ];

            $records[] = $record;
            $bySourceId[$sourceId] = $record;
        }

        return [
            'records' => $records,
            'by_source_id' => $bySourceId,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array{by_source_id: array<int, array<string, mixed>>}  $provinces
     * @param  array<int, string>  $skipped
     * @param  array<int, string>  $missingReferences
     * @return array{records: array<int, array<string, mixed>>, by_source_id: array<int, array<string, mixed>>}
     */
    private function convertCities(array $rows, array $provinces, array &$skipped, array &$missingReferences): array
    {
        $records = [];
        $bySourceId = [];
        $usedSlugs = [];

        foreach ($rows as $index => $row) {
            $sourceId = $this->intValue($this->firstValue($row, ['id', 'city_id', 'source_id', 'column_0']), $index + 1);
            $provinceSourceId = $this->intValue($this->firstValue($row, ['province_id', 'ostan_id', 'parent_id', 'column_1']), 0);
            $name = $this->nameValue($this->firstValue($row, ['name_fa', 'name', 'title', 'city', 'column_2', 'column_1']));
            $province = $provinces['by_source_id'][$provinceSourceId] ?? null;

            if ($name === '') {
                $skipped[] = "cities row [{$index}] missing name.";

                continue;
            }

            if ($province === null) {
                $missingReferences[] = "cities row [{$index}] references missing province source id [{$provinceSourceId}].";

                continue;
            }

            $code = $this->cityCode($provinceSourceId, $sourceId);
            $record = [
                'code' => $code,
                'source_id' => $sourceId,
                'province_code' => $province['code'],
                'province_source_id' => $provinceSourceId,
                'name_fa' => $name,
                'name_en' => null,
                'slug' => $this->uniqueSlug($this->slug($name, "city-{$provinceSourceId}-{$sourceId}"), $usedSlugs, $sourceId),
                'normalized_name' => $this->normalizer->search($name),
                'display_name_fa' => null,
                'is_province_capital' => false,
                'latitude' => $this->nullableFloat($this->firstValue($row, ['latitude', 'lat'])),
                'longitude' => $this->nullableFloat($this->firstValue($row, ['longitude', 'lng', 'lon'])),
                'is_active' => true,
                'source' => 'package',
                'source_version' => self::SOURCE_VERSION,
                'data_version' => self::DATA_VERSION,
            ];

            $records[] = $record;
            $bySourceId[$sourceId] = $record + ['province_source_id' => $provinceSourceId];
        }

        return [
            'records' => $records,
            'by_source_id' => $bySourceId,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array{by_source_id: array<int, array<string, mixed>>}  $cities
     * @param  array<int, string>  $skipped
     * @param  array<int, string>  $missingReferences
     * @return array<int, array<string, mixed>>
     */
    private function convertNeighborhoods(array $rows, array $cities, array &$skipped, array &$missingReferences): array
    {
        $records = [];
        $usedSlugs = [];

        foreach ($rows as $index => $row) {
            $sourceId = $this->intValue($this->firstValue($row, ['id', 'district_id', 'neighborhood_id', 'source_id', 'column_0']), $index + 1);
            $citySourceId = $this->intValue($this->firstValue($row, ['city_id', 'parent_id', 'column_1']), 0);
            $name = $this->nameValue($this->firstValue($row, ['name_fa', 'name', 'title', 'district', 'neighborhood', 'column_2', 'column_1']));
            $city = $cities['by_source_id'][$citySourceId] ?? null;

            if ($name === '') {
                $skipped[] = "neighborhoods row [{$index}] missing name.";

                continue;
            }

            if ($city === null) {
                $missingReferences[] = "neighborhoods row [{$index}] references missing city source id [{$citySourceId}].";

                continue;
            }

            $provinceSourceId = (int) ($city['province_source_id'] ?? 0);
            $code = $this->neighborhoodCode($provinceSourceId, $citySourceId, $sourceId);

            $records[] = [
                'code' => $code,
                'source_id' => $sourceId,
                'city_code' => $city['code'],
                'city_source_id' => $citySourceId,
                'name_fa' => $name,
                'name_en' => null,
                'slug' => $this->uniqueSlug($this->slug($name, "neighborhood-{$citySourceId}-{$sourceId}"), $usedSlugs, $sourceId),
                'normalized_name' => $this->normalizer->search($name),
                'display_name_fa' => null,
                'type' => $this->neighborhoodType($name),
                'latitude' => $this->nullableFloat($this->firstValue($row, ['latitude', 'lat'])),
                'longitude' => $this->nullableFloat($this->firstValue($row, ['longitude', 'lng', 'lon'])),
                'is_active' => true,
                'source' => 'package',
                'source_version' => self::SOURCE_VERSION,
                'data_version' => self::DATA_VERSION,
            ];
        }

        return $records;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readSqlFile(string $sourcePath, string $file): array
    {
        $path = $sourcePath.DIRECTORY_SEPARATOR.$file;

        if (! is_file($path)) {
            return [];
        }

        return $this->parseSql((string) file_get_contents($path));
    }

    /**
     * @return array<int, array{columns: string, values: string}>
     */
    private function insertStatements(string $sql): array
    {
        $statements = [];
        $length = strlen($sql);
        $quoted = false;
        $offset = 0;

        while (preg_match('/insert\s+into\s+[`"\[]?[\w.-]+[`"\]]?\s*(?:\((?<columns>.*?)\))?\s+values\s*/is', $sql, $matches, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $valuesStart = $matches[0][1] + strlen($matches[0][0]);
            $quoted = false;

            for ($index = $valuesStart; $index < $length; $index++) {
                $char = $sql[$index];

                if ($char === "'" && ($index === 0 || $sql[$index - 1] !== '\\')) {
                    if ($quoted && ($sql[$index + 1] ?? null) === "'") {
                        $index++;

                        continue;
                    }

                    $quoted = ! $quoted;
                }

                if ($char === ';' && ! $quoted) {
                    $statements[] = [
                        'columns' => isset($matches['columns'][0]) ? (string) $matches['columns'][0] : '',
                        'values' => substr($sql, $valuesStart, $index - $valuesStart),
                    ];
                    $offset = $index + 1;

                    break;
                }
            }

            if ($index >= $length) {
                break;
            }
        }

        return $statements;
    }

    /**
     * @return array<int, string>
     */
    private function splitColumns(string $columns): array
    {
        return array_map(
            static fn (string $column): string => trim($column, " \t\n\r\0\x0B`\"[]"),
            explode(',', $columns),
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function valueGroups(string $values): array
    {
        $groups = [];
        $buffer = '';
        $depth = 0;
        $quoted = false;
        $length = strlen($values);

        for ($index = 0; $index < $length; $index++) {
            $char = $values[$index];

            if ($char === "'" && ($index === 0 || $values[$index - 1] !== '\\')) {
                if ($quoted && ($values[$index + 1] ?? null) === "'") {
                    $buffer .= $char.$values[++$index];

                    continue;
                }

                $quoted = ! $quoted;
            }

            if ($char === '(' && ! $quoted) {
                $depth++;

                if ($depth === 1) {
                    $buffer = '';

                    continue;
                }
            }

            if ($char === ')' && ! $quoted) {
                $depth--;

                if ($depth === 0) {
                    $groups[] = $this->parseValues($buffer);
                    $buffer = '';

                    continue;
                }
            }

            if ($depth > 0) {
                $buffer .= $char;
            }
        }

        return $groups;
    }

    /**
     * @return array<int, mixed>
     */
    private function parseValues(string $values): array
    {
        $parts = [];
        $buffer = '';
        $quoted = false;
        $length = strlen($values);

        for ($index = 0; $index < $length; $index++) {
            $char = $values[$index];

            if ($char === "'" && ($index === 0 || $values[$index - 1] !== '\\')) {
                if ($quoted && ($values[$index + 1] ?? null) === "'") {
                    $buffer .= $char.$values[++$index];

                    continue;
                }

                $quoted = ! $quoted;
                $buffer .= $char;

                continue;
            }

            if ($char === ',' && ! $quoted) {
                $parts[] = $this->parseValue($buffer);
                $buffer = '';

                continue;
            }

            $buffer .= $char;
        }

        $parts[] = $this->parseValue($buffer);

        return $parts;
    }

    private function parseValue(string $value): mixed
    {
        $value = trim($value);

        if (strcasecmp($value, 'null') === 0) {
            return null;
        }

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return str_replace(["\\'", "''"], ["'", "'"], substr($value, 1, -1));
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<string, mixed>
     */
    private function rowWithoutColumns(array $values): array
    {
        $row = [];

        foreach ($values as $index => $value) {
            $row['column_'.$index] = $value;
        }

        return $row;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, mixed>  $values
     * @return array<string, mixed>
     */
    private function rowWithColumns(array $columns, array $values): array
    {
        $row = [];

        foreach ($columns as $index => $column) {
            $row[$column] = $values[$index] ?? null;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstValue(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        return null;
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

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function provinceCode(int $sourceId): string
    {
        return sprintf('ir.province.%03d', $sourceId);
    }

    private function cityCode(int $provinceSourceId, int $sourceId): string
    {
        return sprintf('ir.city.%03d.%04d', $provinceSourceId, $sourceId);
    }

    private function neighborhoodCode(int $provinceSourceId, int $citySourceId, int $sourceId): string
    {
        return sprintf('ir.neighborhood.%03d.%04d.%04d', $provinceSourceId, $citySourceId, $sourceId);
    }

    private function slug(string $name, string $fallback): string
    {
        $slug = $this->normalizer->slug($name);

        if ($slug === '' || preg_match('/[a-z0-9]/i', $slug) !== 1) {
            return Str::slug($fallback);
        }

        return $slug;
    }

    /**
     * @param  array<string, true>  $used
     */
    private function uniqueSlug(string $slug, array &$used, int $sourceId): string
    {
        $candidate = $slug;

        if (isset($used[$candidate])) {
            $candidate = $slug.'-'.$sourceId;
        }

        $used[$candidate] = true;

        return $candidate;
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
                'name' => 'project-sql-import',
                'version' => self::SOURCE_VERSION,
                'files' => [
                    'provinces.sql',
                    'cities.sql',
                    'districts.sql',
                ],
            ],
            'contains' => [
                'provinces' => $counts['provinces'] > 0,
                'cities' => $counts['cities'] > 0,
                'city_regions' => false,
                'city_areas' => false,
                'neighborhoods' => $counts['neighborhoods'] > 0,
                'aliases' => false,
            ],
            'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
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
}
