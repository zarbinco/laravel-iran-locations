<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\ExcelLocationDataConverter;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Tests\TestCase;

class ExcelLocationDataConverterTest extends TestCase
{
    public function test_converter_builds_excel_hierarchy_with_contract_normalization_and_municipal_mappings(): void
    {
        $outputPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'iran-locations-excel-convert-'.bin2hex(random_bytes(6));
        $converter = new ExcelLocationDataConverter($this->fakeNormalizer());

        $summary = $converter->convertRows(
            [
                ['کد استان', 'نام استان', 'کد شهرستان', 'نام شهرستان', 'کد بخش', 'نام بخش', 'نام شهر'],
                ['23', 'تهران', '01', 'تهران', '02', 'مرکزی', 'تهران'],
                ['23', 'تهران', '02', 'ری', '01', 'مرکزی', 'ري'],
            ],
            [
                ['تقسیمات کشوری استان تهران', null, null],
                ['شهرستان و بخش', 'نام شهر', 'نام دهستان'],
                [],
                ['شهرستان تهران', 'تهران', 'سیاهرود-کن'],
                ['بخش مرکزی', 'تهران', 'سیاهرود'],
                ['بخش کن', null, 'کن'],
            ],
            [
                ['لیست مناطق تهران به همراه محله‌های هر منطقه'],
                ['ردیف', 'شماره منطقه', 'نام منطقه', 'محله/آیتم طبق منبع', 'ردیف در منطقه', 'تکراری در کل فایل؟'],
                [1, 1, 'منطقه ۱ تهران', 'محله نمونه', 1, 'خیر'],
            ],
            $outputPath,
        );

        $provinces = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('provinces'));
        $counties = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('counties'));
        $officialDistricts = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('official_districts'));
        $ruralDistricts = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('rural_districts'));
        $cities = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('cities'));
        $cityRegions = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('city_regions'));
        $neighborhoods = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('neighborhoods'));
        $neighborhoodRegion = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::fileFor('neighborhood_region'));
        $manifest = $this->readJson($outputPath.DIRECTORY_SEPARATOR.LocationDataManifest::MANIFEST_FILE);

        self::assertSame(1, $summary['counts']['provinces']);
        self::assertSame(2, $summary['counts']['counties']);
        self::assertSame(3, $summary['counts']['official_districts']);
        self::assertSame(2, $summary['counts']['rural_districts']);
        self::assertSame(2, $summary['counts']['cities']);
        self::assertSame(1, $summary['counts']['city_regions']);
        self::assertSame(1, $summary['counts']['neighborhoods']);
        self::assertSame(1, $summary['counts']['neighborhood_region']);
        self::assertSame('search:تهران', $provinces[0]['normalized_name']);
        self::assertSame('ری', $cities[1]['name_fa']);
        self::assertSame('search:ری', $cities[1]['normalized_name']);
        self::assertSame('slug:تهران', $cities[0]['slug']);
        self::assertSame('official-district-1', $officialDistricts[0]['slug']);
        self::assertSame('rural-district-1', $ruralDistricts[0]['slug']);
        self::assertSame('p.01', $provinces[0]['code']);
        self::assertSame('c.01.01', $counties[0]['code']);
        self::assertSame('b.01.01.01', $officialDistricts[0]['code']);
        self::assertSame('s.01.01.01.01', $cities[0]['code']);
        self::assertSame('r.01.01.01.01.01', $cityRegions[0]['code']);
        self::assertSame('n.01.01.01.01.01.001', $neighborhoods[0]['code']);
        self::assertSame('r.01.01.01.01.01', $neighborhoods[0]['default_city_region_code']);
        self::assertSame('zarbin-iran-location-code', $manifest['code_scheme']['name']);
        self::assertSame($neighborhoods[0]['code'], $neighborhoodRegion[0]['neighborhood_code']);
        self::assertFileDoesNotExist($outputPath.DIRECTORY_SEPARATOR.'districts.json');
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
                return $value === 'تهران' ? 'slug:'.$value : '';
            }
        };
    }

    /**
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    private function readJson(string $path): array
    {
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($data);

        return $data;
    }
}
