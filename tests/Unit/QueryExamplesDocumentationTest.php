<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Tests\TestCase;

class QueryExamplesDocumentationTest extends TestCase
{
    public function test_query_examples_document_exists_and_uses_tehran_context(): void
    {
        $contents = $this->queryExamples();

        self::assertStringContainsString('# Query Examples With Tehran City', $contents);
        self::assertStringContainsString('Tehran city', $contents);
        self::assertStringContainsString('ir.province.001', $contents);
        self::assertStringContainsString('ir.county.001.001', $contents);
        self::assertStringContainsString('ir.city.001.001.001.001', $contents);
        self::assertStringContainsString('ir.city.tehran.region.05', $contents);
        self::assertStringContainsString('City areas and aliases are structurally supported', $contents);
        self::assertStringContainsString('zero alias records', $contents);
    }

    public function test_readme_links_to_query_examples(): void
    {
        $readme = file_get_contents($this->path('README.md'));

        self::assertIsString($readme);
        self::assertStringContainsString('(docs/query-examples.md)', $readme);
    }

    public function test_hard_coded_location_codes_exist_in_packaged_data(): void
    {
        preg_match_all('/\bir\.[a-z_]+(?:\.[a-z0-9_]+)+\b/i', $this->queryExamples(), $matches);

        $codes = array_values(array_unique($matches[0]));

        self::assertNotEmpty($codes);

        foreach ($codes as $code) {
            $dataset = $this->datasetForCode($code);

            self::assertContains($code, $this->codes($dataset), "{$code} is not present in {$dataset}.json.");
        }
    }

    public function test_query_examples_avoid_removed_config_keys_and_fqcn_alias_inputs(): void
    {
        $contents = $this->queryExamples();

        self::assertStringNotContainsString('normalization.'.'on_sync', $contents);
        self::assertStringNotContainsString('data.'.'preserve_custom_records', $contents);
        self::assertDoesNotMatchRegularExpression('/location_type\s*=\s*(?:Zarbin|\\\\|%5C)/i', $contents);
    }

    public function test_city_area_placeholders_explain_empty_packaged_data(): void
    {
        $contents = $this->queryExamples();

        self::assertStringContainsString('/city-areas/{area-code}/neighborhoods', $contents);
        self::assertStringContainsString('Packaged city areas are empty in `0.2.0-dev`', $contents);
    }

    private function queryExamples(): string
    {
        $path = $this->path('docs/query-examples.md');

        self::assertFileExists($path);

        $contents = file_get_contents($path);

        self::assertIsString($contents);

        return $contents;
    }

    /**
     * @return array<int, string>
     */
    private function codes(string $dataset): array
    {
        $records = json_decode((string) file_get_contents($this->path("data/{$dataset}.json")), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($records);

        return array_values(array_filter(array_column($records, 'code'), 'is_string'));
    }

    private function datasetForCode(string $code): string
    {
        return match (true) {
            str_starts_with($code, 'ir.province.') => 'provinces',
            str_starts_with($code, 'ir.county.') => 'counties',
            str_starts_with($code, 'ir.official_district.') => 'official_districts',
            str_starts_with($code, 'ir.rural_district.') => 'rural_districts',
            str_starts_with($code, 'ir.city.tehran.region.') => 'city_regions',
            str_starts_with($code, 'ir.city_area.') => 'city_areas',
            str_starts_with($code, 'ir.city.') => 'cities',
            str_starts_with($code, 'ir.neighborhood.') => 'neighborhoods',
            default => self::fail("No dataset mapping exists for {$code}."),
        };
    }

    private function path(string $file): string
    {
        return dirname(__DIR__, 2).'/'.$file;
    }
}
