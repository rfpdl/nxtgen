<?php

declare(strict_types=1);

namespace App\Services\Church\Providers;

use App\Foundation\Support\SatelliteCode;
use App\Services\Church\Http\Middleware\RequireAssignment;
use App\Services\Church\Http\Middleware\RequireSessionAssignment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', RequireAssignment::class])
            ->prefix('{satellite}')
            ->group(__DIR__.'/../routes/web.php');

        // An opened class's own address (spec item 16): its code, never an id — the one exception to {satellite}-first.
        Route::middleware(['web', 'auth', RequireSessionAssignment::class])
            ->prefix('{session}')
            ->where(['session' => '['.SatelliteCode::ALPHABET.']{'.SatelliteCode::LENGTH.'}'])
            ->group(__DIR__.'/../routes/session.php');
    }
}
