<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\County;
use Zarbin\IranLocations\Models\LocationDataVersion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\OfficialDistrict;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Models\RuralDistrict;

class AdminDashboardDataTest extends AdminTestCase
{
    public function test_dashboard_and_data_status_render(): void
    {
        $this->get(route('iran-locations.admin.dashboard'))
            ->assertOk()
            ->assertSee('Iran Locations')
            ->assertSee('Package counts');

        $this->get(route('iran-locations.admin.data.index'))
            ->assertOk()
            ->assertSee('Data status')
            ->assertSee('Dry-run sync');
    }

    public function test_dry_run_sync_through_ui_does_not_create_records(): void
    {
        $this->post(route('iran-locations.admin.data.sync'), ['dry_run' => '1'])
            ->assertRedirect(route('iran-locations.admin.data.index'));

        self::assertSame(0, Province::query()->count());
        self::assertSame(0, LocationDataVersion::query()->count());

        $this->get(route('iran-locations.admin.data.index'))
            ->assertOk()
            ->assertSee('Dry-run sync completed')
            ->assertSee('provinces');
    }

    public function test_apply_sync_through_ui_creates_package_records_safely(): void
    {
        $this->post(route('iran-locations.admin.data.sync'))
            ->assertRedirect(route('iran-locations.admin.data.index'));

        self::assertSame(31, Province::query()->count());
        self::assertSame(484, County::query()->count());
        self::assertSame(1087, OfficialDistrict::query()->count());
        self::assertSame(73, RuralDistrict::query()->count());
        self::assertSame(1456, City::query()->count());
        self::assertSame(568, Neighborhood::query()->count());
        self::assertSame(1, LocationDataVersion::query()->count());

        $this->get(route('iran-locations.admin.data.index'))
            ->assertOk()
            ->assertSee('Data sync completed')
            ->assertSee('Created');
    }
}
