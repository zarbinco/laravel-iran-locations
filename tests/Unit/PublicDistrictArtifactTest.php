<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Tests\TestCase;

class PublicDistrictArtifactTest extends TestCase
{
    public function test_no_ambiguous_public_district_artifacts_are_registered(): void
    {
        self::assertFalse(class_exists('Zarbin\\IranLocations\\Models\\District'));
        self::assertFileDoesNotExist(dirname(__DIR__, 2).'/src/Models/District.php');
        self::assertFileDoesNotExist(dirname(__DIR__, 2).'/data/districts.json');

        self::assertArrayNotHasKey('district', config('iran-locations.models'));
        self::assertArrayNotHasKey('district', config('iran-locations.tables'));
        self::assertArrayNotHasKey('districts', config('iran-locations.tables'));
    }
}
