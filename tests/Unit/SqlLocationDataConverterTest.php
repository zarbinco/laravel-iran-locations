<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use RuntimeException;
use Zarbin\IranLocations\Data\SqlLocationDataConverter;
use Zarbin\IranLocations\Tests\TestCase;

class SqlLocationDataConverterTest extends TestCase
{
    public function test_converter_rejects_non_canonical_public_data_generation(): void
    {
        $outputPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'iran-locations-convert-'.bin2hex(random_bytes(6));
        $converter = new SqlLocationDataConverter;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQL conversion is a non-canonical utility and cannot generate public package codes without full county and official-district hierarchy.');

        $converter->convertRows(
            [
                ['id' => 1, 'name' => 'تهران'],
            ],
            [
                ['id' => 1, 'province_id' => 1, 'name' => 'تهران'],
            ],
            [
                ['id' => 1, 'city_id' => 1, 'name' => 'خیابان وليعصر'],
                ['id' => 2, 'city_id' => 1, 'name' => 'پارک ملت'],
            ],
            $outputPath,
        );
    }

    public function test_converter_parses_phpmyadmin_dump_style_insert_statements(): void
    {
        $converter = new SqlLocationDataConverter;

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
}
