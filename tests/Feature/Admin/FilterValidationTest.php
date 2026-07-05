<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Zarbin\IranLocations\Tests\Support\CreatesLocationTestData;

class FilterValidationTest extends AdminTestCase
{
    use CreatesLocationTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindFakeLocationNormalizer();
    }

    public function test_admin_index_id_filters_reject_negative_integers(): void
    {
        $this->get(route('iran-locations.admin.cities.index', ['province_id' => -1]))
            ->assertSessionHasErrors(['province_id']);
    }

    public function test_admin_index_id_filters_accept_positive_integers(): void
    {
        $records = $this->createLocationGraph('admin-valid-filter');

        $this->get(route('iran-locations.admin.cities.index', ['province_id' => $records['province']->getKey()]))
            ->assertOk()
            ->assertSee('City admin-valid-filter');
    }
}
