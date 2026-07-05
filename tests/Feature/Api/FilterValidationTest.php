<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Api;

class FilterValidationTest extends ApiTestCase
{
    public function test_api_index_id_filters_reject_negative_integers(): void
    {
        $this->getJson('/iran-locations/api/cities?province_id=-1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['province_id']);
    }

    public function test_api_index_id_filters_accept_positive_integers(): void
    {
        $records = $this->createLocationGraph('api-valid-filter');

        $this->getJson('/iran-locations/api/cities?province_id='.$records['province']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-api-valid-filter');
    }
}
