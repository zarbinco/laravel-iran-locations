<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Data\SqlLocationDataConverter;
use Zarbin\IranLocations\Tests\TestCase;

class SqlLocationDataConverterTest extends TestCase
{
    public function test_converter_maps_district_rows_to_neighborhoods_with_types_and_contract_normalization(): void
    {
        $outputPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'iran-locations-convert-'.bin2hex(random_bytes(6));
        $converter = new SqlLocationDataConverter($this->fakeNormalizer());

        $summary = $converter->convertRows(
            [
                ['id' => 1, 'name' => 'تهران'],
            ],
            [
                ['id' => 1, 'province_id' => 1, 'name' => 'تهران'],
            ],
            [
                ['id' => 1, 'city_id' => 1, 'name' => 'خیابان ولیعصر'],
                ['id' => 2, 'city_id' => 1, 'name' => 'پارک ملت'],
            ],
            $outputPath,
        );

        $neighborhoods = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('neighborhoods'));

        self::assertSame(2, $summary['counts']['neighborhoods']);
        self::assertSame('street', $neighborhoods[0]['type']);
        self::assertSame('park', $neighborhoods[1]['type']);
        self::assertSame('search:خیابان ولیعصر', $neighborhoods[0]['normalized_name']);
        self::assertSame('street-slug', $neighborhoods[0]['slug']);
        self::assertSame('neighborhood-1-2', $neighborhoods[1]['slug']);
        self::assertSame('ir.neighborhood.001.0001.0001', $neighborhoods[0]['code']);
        self::assertFileDoesNotExist($outputPath.DIRECTORY_SEPARATOR.'districts.json');
    }

    public function test_converter_parses_phpmyadmin_dump_style_insert_statements(): void
    {
        $converter = new SqlLocationDataConverter($this->fakeNormalizer());

        $rows = $converter->parseSql(<<<'SQL'
-- phpMyAdmin SQL Dump
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
/*!40101 SET NAMES utf8mb4 */;

--
-- Dumping data for table `provinces`
--

INSERT INTO `provinces` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'تهران', '2026-06-06 00:20:15', '2026-06-06 00:20:15'),
(2, 'قم', '2026-06-06 00:20:15', '2026-06-06 00:20:15');

--
-- Another insert
--

INSERT INTO `provinces` (`id`, `name`) VALUES
(3, 'البرز');

COMMIT;
SQL);

        self::assertCount(3, $rows);
        self::assertSame(1, $rows[0]['id']);
        self::assertSame('تهران', $rows[0]['name']);
        self::assertSame(2, $rows[1]['id']);
        self::assertSame('قم', $rows[1]['name']);
        self::assertSame(3, $rows[2]['id']);
        self::assertSame('البرز', $rows[2]['name']);
    }

    private function fakeNormalizer(): LocationNormalizer
    {
        return new class implements LocationNormalizer
        {
            public function display(string $value): string
            {
                return 'display:'.$value;
            }

            public function search(string $value): string
            {
                return 'search:'.$value;
            }

            public function slug(string $value): string
            {
                return str_contains($value, 'خیابان') ? 'street-slug' : '';
            }
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $path): array
    {
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($data);

        return $data;
    }
}
