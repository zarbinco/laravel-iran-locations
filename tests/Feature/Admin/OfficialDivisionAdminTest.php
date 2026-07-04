<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Zarbin\IranLocations\Models\County;
use Zarbin\IranLocations\Models\OfficialDistrict;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Models\RuralDistrict;

class OfficialDivisionAdminTest extends AdminTestCase
{
    public function test_county_admin_crud_filters_and_safe_destroy_work(): void
    {
        $province = $this->province();
        $otherProvince = $this->province('other');

        $this->get(route('iran-locations.admin.counties.index'))
            ->assertOk()
            ->assertSee('Counties');

        $this->post(route('iran-locations.admin.counties.store'), [
            'province_id' => $province->getKey(),
            'code' => 'admin.county',
            'name_fa' => 'Admin County',
            'is_active' => '1',
        ])->assertRedirect();

        $county = County::query()->where('code', 'admin.county')->firstOrFail();

        self::assertSame('custom', $county->getAttribute('source'));

        County::query()->create([
            'province_id' => $otherProvince->getKey(),
            'code' => 'admin.county.other',
            'name_fa' => 'Other County',
            'normalized_name' => 'other county',
        ]);

        $this->get(route('iran-locations.admin.counties.index', [
            'q' => 'Admin',
            'province_id' => $province->getKey(),
        ]))->assertOk()
            ->assertSee('Admin County')
            ->assertDontSee('Other County');

        $this->put(route('iran-locations.admin.counties.update', $county->getKey()), [
            'province_id' => $province->getKey(),
            'code' => 'admin.county',
            'name_fa' => 'Updated County',
            'is_active' => '1',
            'source' => 'custom',
        ])->assertRedirect();

        self::assertSame('Updated County', $county->refresh()->getAttribute('name_fa'));

        $this->delete(route('iran-locations.admin.counties.destroy', $county->getKey()))
            ->assertRedirect()
            ->assertSessionHas('status', 'County was deleted.');

        self::assertFalse(County::query()->whereKey($county->getKey())->exists());

        $packageCounty = County::query()->create([
            'province_id' => $province->getKey(),
            'code' => 'admin.county.package',
            'name_fa' => 'Package County',
            'normalized_name' => 'package county',
            'source' => 'package',
        ]);

        $this->delete(route('iran-locations.admin.counties.destroy', $packageCounty->getKey()))
            ->assertRedirect();

        self::assertFalse((bool) $packageCounty->refresh()->getAttribute('is_active'));
        self::assertNotNull($packageCounty->getAttribute('deprecated_at'));
    }

    public function test_official_district_admin_crud_filters_and_validation_work(): void
    {
        $province = $this->province();
        $county = $this->county($province);
        $otherCounty = $this->county($province, 'other');

        $this->post(route('iran-locations.admin.official-districts.store'), [
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'code' => 'admin.official',
            'name_fa' => 'Admin Official District',
            'is_active' => '1',
        ])->assertRedirect();

        $officialDistrict = OfficialDistrict::query()->where('code', 'admin.official')->firstOrFail();

        OfficialDistrict::query()->create([
            'province_id' => $province->getKey(),
            'county_id' => $otherCounty->getKey(),
            'code' => 'admin.official.other',
            'name_fa' => 'Other Official District',
            'normalized_name' => 'other official district',
        ]);

        $this->get(route('iran-locations.admin.official-districts.index', [
            'county_id' => $county->getKey(),
            'q' => 'Official',
        ]))->assertOk()
            ->assertSee('Admin Official District')
            ->assertDontSee('Other Official District');

        $this->put(route('iran-locations.admin.official-districts.update', $officialDistrict->getKey()), [
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'code' => 'admin.official',
            'name_fa' => 'Updated Official District',
            'is_active' => '1',
            'source' => 'custom',
        ])->assertRedirect();

        self::assertSame('Updated Official District', $officialDistrict->refresh()->getAttribute('name_fa'));

        $this->post(route('iran-locations.admin.official-districts.store'), [
            'province_id' => $province->getKey(),
            'county_id' => 999,
            'code' => 'admin.official.missing',
            'name_fa' => 'Missing County',
        ])->assertSessionHasErrors(['county_id']);
    }

    public function test_rural_district_admin_crud_filters_and_safe_destroy_work(): void
    {
        $province = $this->province();
        $county = $this->county($province);
        $officialDistrict = $this->officialDistrict($province, $county);
        $otherOfficialDistrict = $this->officialDistrict($province, $county, 'other');

        $this->post(route('iran-locations.admin.rural-districts.store'), [
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'official_district_id' => $officialDistrict->getKey(),
            'code' => 'admin.rural',
            'name_fa' => 'Admin Rural District',
            'is_active' => '1',
        ])->assertRedirect();

        $ruralDistrict = RuralDistrict::query()->where('code', 'admin.rural')->firstOrFail();

        RuralDistrict::query()->create([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'official_district_id' => $otherOfficialDistrict->getKey(),
            'code' => 'admin.rural.other',
            'name_fa' => 'Other Rural District',
            'normalized_name' => 'other rural district',
        ]);

        $this->get(route('iran-locations.admin.rural-districts.index', [
            'official_district_id' => $officialDistrict->getKey(),
            'q' => 'Rural',
        ]))->assertOk()
            ->assertSee('Admin Rural District')
            ->assertDontSee('Other Rural District');

        $this->put(route('iran-locations.admin.rural-districts.update', $ruralDistrict->getKey()), [
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'official_district_id' => $officialDistrict->getKey(),
            'code' => 'admin.rural',
            'name_fa' => 'Updated Rural District',
            'is_active' => '1',
            'source' => 'custom',
        ])->assertRedirect();

        self::assertSame('Updated Rural District', $ruralDistrict->refresh()->getAttribute('name_fa'));
    }

    private function province(string $suffix = 'main'): Province
    {
        $province = new Province([
            'code' => 'admin.province.'.$suffix,
            'name_fa' => 'Admin Province '.$suffix,
            'normalized_name' => 'admin province '.$suffix,
            'source' => 'custom',
        ]);

        $province->save();

        return $province;
    }

    private function county(Province $province, string $suffix = 'main'): County
    {
        $county = new County([
            'province_id' => $province->getKey(),
            'code' => 'admin.county.'.$suffix,
            'name_fa' => 'Admin County '.$suffix,
            'normalized_name' => 'admin county '.$suffix,
            'source' => 'custom',
        ]);

        $county->save();

        return $county;
    }

    private function officialDistrict(Province $province, County $county, string $suffix = 'main'): OfficialDistrict
    {
        $officialDistrict = new OfficialDistrict([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'code' => 'admin.official.'.$suffix,
            'name_fa' => 'Admin Official '.$suffix,
            'normalized_name' => 'admin official '.$suffix,
            'source' => 'custom',
        ]);

        $officialDistrict->save();

        return $officialDistrict;
    }
}
