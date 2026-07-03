<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Gate;

trait AuthorizesIranLocationsAdmin
{
    protected function authorizeIranLocationsAdmin(): void
    {
        $gate = config('iran-locations.admin.gate');

        if (is_string($gate) && $gate !== '') {
            Gate::authorize($gate);
        }
    }
}
