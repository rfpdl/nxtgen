<?php

declare(strict_types=1);

namespace App\Services\Church\Providers;

use App\Services\Church\Http\Middleware\RequireAssignment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', RequireAssignment::class])
            ->prefix('{church:slug}')
            ->group(__DIR__.'/../routes/web.php');
    }
}
