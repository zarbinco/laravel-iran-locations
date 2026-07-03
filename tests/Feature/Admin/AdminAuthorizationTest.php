<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Illuminate\Support\Facades\Gate;

class AdminAuthorizationTest extends AdminTestCase
{
    public function test_null_gate_allows_admin_access(): void
    {
        config()->set('iran-locations.admin.gate', null);

        $this->get(route('iran-locations.admin.dashboard'))->assertOk();
    }

    public function test_configured_gate_can_allow_admin_access(): void
    {
        config()->set('iran-locations.admin.gate', 'manageIranLocations');
        Gate::define('manageIranLocations', static fn ($user = null): bool => true);

        $this->get(route('iran-locations.admin.dashboard'))->assertOk();
    }

    public function test_configured_gate_can_deny_admin_access(): void
    {
        config()->set('iran-locations.admin.gate', 'manageIranLocations');
        Gate::define('manageIranLocations', static fn ($user = null): bool => false);

        $this->get(route('iran-locations.admin.dashboard'))->assertForbidden();
    }
}
