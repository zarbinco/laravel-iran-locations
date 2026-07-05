<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use InvalidArgumentException;
use Zarbin\IranLocations\Coding\LocationCodeGenerator;
use Zarbin\IranLocations\Tests\TestCase;

class LocationCodeGeneratorTest extends TestCase
{
    public function test_generator_builds_expected_location_codes(): void
    {
        $codes = new LocationCodeGenerator;

        $province = $codes->province(1);
        $county = $codes->county($province, 2);
        $officialDistrict = $codes->officialDistrict($county, 3);
        $ruralDistrict = $codes->ruralDistrict($officialDistrict, 4);
        $city = $codes->city($officialDistrict, 5);
        $cityRegion = $codes->cityRegion($city, 6);
        $cityArea = $codes->cityArea($cityRegion, 7);
        $neighborhood = $codes->neighborhood($city, $cityRegion, 8);

        self::assertSame('p.01', $province);
        self::assertSame('c.01.02', $county);
        self::assertSame('b.01.02.03', $officialDistrict);
        self::assertSame('d.01.02.03.04', $ruralDistrict);
        self::assertSame('s.01.02.03.05', $city);
        self::assertSame('r.01.02.03.05.06', $cityRegion);
        self::assertSame('a.01.02.03.05.06.07', $cityArea);
        self::assertSame('n.01.02.03.05.06.008', $neighborhood);
    }

    public function test_generator_rejects_zero_negative_and_overflow_segments(): void
    {
        $codes = new LocationCodeGenerator;

        foreach ([0, -1, 100] as $segment) {
            try {
                $codes->province($segment);
                self::fail('Expected province segment rejection.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('does not fit a 2-digit code segment', $exception->getMessage());
            }
        }

        $cityRegion = $codes->cityRegion($codes->city($codes->officialDistrict($codes->county($codes->province(1), 1), 1), 1), 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not fit a 3-digit code segment');

        $codes->neighborhood('s.01.01.01.01', $cityRegion, 1000);
    }

    public function test_generator_rejects_region_from_another_city_for_neighborhoods(): void
    {
        $codes = new LocationCodeGenerator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not belong to city code');

        $codes->neighborhood('s.01.01.01.01', 'r.01.01.01.02.01', 1);
    }
}
