<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Api;

class SearchMinLengthValidationTest extends ApiTestCase
{
    public function test_search_endpoint_enforces_configured_minimum_query_length(): void
    {
        $this->getJson('/iran-locations/api/search?q=a')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->getJson('/iran-locations/api/search?q=ab')
            ->assertOk()
            ->assertJsonPath('query', 'ab');
    }

    public function test_optional_query_api_endpoint_rejects_short_query_when_present(): void
    {
        $this->createLocationGraph('optional-search-min');

        $this->getJson('/iran-locations/api/cities?q=a')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->getJson('/iran-locations/api/cities')
            ->assertOk();
    }

    public function test_option_and_alias_api_requests_enforce_minimum_query_length_when_present(): void
    {
        $this->getJson('/iran-locations/api/options/provinces?q=a')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->getJson('/iran-locations/api/aliases?q=a')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_search_minimum_length_uses_dynamic_config_value(): void
    {
        config()->set('iran-locations.search.min_length', 3);

        $this->getJson('/iran-locations/api/search?q=ab')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->getJson('/iran-locations/api/search?q=abc')
            ->assertOk()
            ->assertJsonPath('query', 'abc');
    }
}
