<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Coding;

use InvalidArgumentException;

final class LocationCodeGenerator
{
    /**
     * @var array<string, string>
     */
    private const PATTERNS = [
        'provinces' => '/^p\.(\d{2})$/',
        'counties' => '/^c\.(\d{2})\.(\d{2})$/',
        'official_districts' => '/^b\.(\d{2})\.(\d{2})\.(\d{2})$/',
        'rural_districts' => '/^d\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})$/',
        'cities' => '/^s\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})$/',
        'city_regions' => '/^r\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})$/',
        'city_areas' => '/^a\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})$/',
        'neighborhoods' => '/^n\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{2})\.(\d{3})$/',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function scheme(): array
    {
        return [
            'name' => 'zarbin-iran-location-code',
            'version' => '1.0',
            'country_prefix' => false,
            'type_prefix' => 'single-letter',
            'segment_format' => 'fixed-width-decimal',
            'legacy_codes_preserved' => false,
        ];
    }

    public function province(int $provinceSequence): string
    {
        return 'p.'.$this->segment($provinceSequence, 2, 'province');
    }

    public function county(string $provinceCode, int $countySequence): string
    {
        return 'c.'.implode('.', [
            ...$this->pathFor('provinces', $provinceCode),
            $this->segment($countySequence, 2, 'county'),
        ]);
    }

    public function officialDistrict(string $countyCode, int $districtSequence): string
    {
        return 'b.'.implode('.', [
            ...$this->pathFor('counties', $countyCode),
            $this->segment($districtSequence, 2, 'official district'),
        ]);
    }

    public function ruralDistrict(string $officialDistrictCode, int $ruralSequence): string
    {
        return 'd.'.implode('.', [
            ...$this->pathFor('official_districts', $officialDistrictCode),
            $this->segment($ruralSequence, 2, 'rural district'),
        ]);
    }

    public function city(string $officialDistrictCode, int $citySequence): string
    {
        return 's.'.implode('.', [
            ...$this->pathFor('official_districts', $officialDistrictCode),
            $this->segment($citySequence, 2, 'city'),
        ]);
    }

    public function cityRegion(string $cityCode, int $regionNumber): string
    {
        return 'r.'.implode('.', [
            ...$this->pathFor('cities', $cityCode),
            $this->segment($regionNumber, 2, 'city region'),
        ]);
    }

    public function cityArea(string $cityRegionCode, int $areaNumber): string
    {
        return 'a.'.implode('.', [
            ...$this->pathFor('city_regions', $cityRegionCode),
            $this->segment($areaNumber, 2, 'city area'),
        ]);
    }

    public function neighborhood(string $cityCode, string $cityRegionCode, int $neighborhoodSequence): string
    {
        $cityPath = $this->pathFor('cities', $cityCode);
        $regionPath = $this->pathFor('city_regions', $cityRegionCode);

        if (! $this->startsWithPath($regionPath, $cityPath)) {
            throw new InvalidArgumentException("City region code [{$cityRegionCode}] does not belong to city code [{$cityCode}].");
        }

        return 'n.'.implode('.', [
            ...$regionPath,
            $this->segment($neighborhoodSequence, 3, 'neighborhood'),
        ]);
    }

    public function matchesDataset(string $dataset, string $code): bool
    {
        $pattern = self::PATTERNS[$dataset] ?? null;

        return $pattern !== null && preg_match($pattern, $code) === 1;
    }

    /**
     * @return array<int, string>
     */
    public function path(string $code): array
    {
        foreach (self::PATTERNS as $dataset => $pattern) {
            if (preg_match($pattern, $code, $matches) === 1) {
                array_shift($matches);

                return $matches;
            }
        }

        throw new InvalidArgumentException("Location code [{$code}] does not match the package code scheme.");
    }

    /**
     * @param  array<int, string>  $childPath
     * @param  array<int, string>  $parentPath
     */
    public function startsWithPath(array $childPath, array $parentPath): bool
    {
        return array_slice($childPath, 0, count($parentPath)) === $parentPath;
    }

    /**
     * @return array<int, string>
     */
    private function pathFor(string $dataset, string $code): array
    {
        $pattern = self::PATTERNS[$dataset] ?? null;

        if ($pattern === null || preg_match($pattern, $code, $matches) !== 1) {
            throw new InvalidArgumentException("Location code [{$code}] is not a valid [{$dataset}] code.");
        }

        array_shift($matches);

        return $matches;
    }

    private function segment(int $value, int $width, string $label): string
    {
        $max = (10 ** $width) - 1;

        if ($value <= 0 || $value > $max) {
            throw new InvalidArgumentException("The {$label} sequence [{$value}] does not fit a {$width}-digit code segment.");
        }

        return str_pad((string) $value, $width, '0', STR_PAD_LEFT);
    }
}
