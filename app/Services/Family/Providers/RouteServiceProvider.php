<?php

declare(strict_types=1);

namespace App\Services\Family\Providers;

use App\Services\Family\Http\Middleware\RequireGuardian;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', RequireGuardian::class])
            ->prefix('{organization:slug}/family')
            ->group(__DIR__.'/../routes/web.php');
    }
}
