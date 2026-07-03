<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Tests\TestCase;

class ModelConfigurationTest extends TestCase
{
    public function test_model_table_name_comes_from_config(): void
    {
        config()->set('iran-locations.tables.provinces', 'custom_provinces');

        self::assertSame('custom_provinces', (new Province)->getTable());
    }

    public function test_display_name_falls_back_to_persian_name(): void
    {
        $province = new Province([
            'name_fa' => 'Tehran',
        ]);

        self::assertSame('Tehran', $province->displayName());
    }
}
