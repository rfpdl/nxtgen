<?php

declare(strict_types=1);

namespace App\Services\Auth\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web'])->group(__DIR__.'/../routes/web.php');
    }
}
