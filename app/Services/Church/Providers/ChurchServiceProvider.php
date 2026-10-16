<?php

declare(strict_types=1);

namespace App\Services\Church\Providers;

use Illuminate\Support\ServiceProvider;

final class ChurchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
