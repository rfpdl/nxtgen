<?php

declare(strict_types=1);

namespace App\Services\Public\Providers;

use Illuminate\Support\ServiceProvider;

final class PublicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
