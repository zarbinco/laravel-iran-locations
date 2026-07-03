<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', static fn () => response()->noContent())
    ->name('iran-locations.admin.index');
