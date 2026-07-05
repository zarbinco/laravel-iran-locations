<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Illuminate\Support\Facades\Gate;
use Zarbin\IranLocations\Models\Province;

class AdminAuthorizationTest extends AdminTestCase
{
    public function test_null_gate_allows_admin_access(): void
    {
        config()->set('iran-locations.admin.gate', null);

        $this->get(route('iran-locations.admin.dashboard'))->assertOk();
    }

    public function test_null_gate_allows_non_resource_admin_route(): void
    {
        config()->set('iran-locations.admin.gate', null);

        $this->get(route('iran-locations.admin.data.index'))->assertOk();
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

    public function test_configured_gate_can_allow_mutating_admin_route(): void
    {
        config()->set('iran-locations.admin.gate', 'manageIranLocations');
        Gate::define('manageIranLocations', static fn ($user = null): bool => true);

        $this->post(route('iran-locations.admin.provinces.store'), [
            'code' => 'gate.allowed.province',
            'name_fa' => 'Gate Allowed Province',
            'is_active' => '1',
        ])->assertRedirect();

        self::assertTrue(Province::query()->where('code', 'gate.allowed.province')->exists());
    }

    public function test_configured_gate_can_deny_mutating_admin_route(): void
    {
        config()->set('iran-locations.admin.gate', 'manageIranLocations');
        Gate::define('manageIranLocations', static fn ($user = null): bool => false);

        $this->post(route('iran-locations.admin.provinces.store'), [
            'code' => 'gate.denied.province',
            'name_fa' => 'Gate Denied Province',
            'is_active' => '1',
        ])->assertForbidden();

        self::assertFalse(Province::query()->where('code', 'gate.denied.province')->exists());
    }
}
