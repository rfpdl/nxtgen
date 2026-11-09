<?php

declare(strict_types=1);

namespace App\Services\Platform\Providers;

use App\Services\Platform\Http\Middleware\RequirePlatformAdmin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', RequirePlatformAdmin::class])
            ->prefix('platform')
            ->name('platform.')
            ->group(__DIR__.'/../routes/web.php');
    }
}
