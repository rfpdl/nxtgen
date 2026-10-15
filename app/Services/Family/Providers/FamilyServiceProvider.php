<?php

declare(strict_types=1);

namespace App\Services\Family\Providers;

use Illuminate\Support\ServiceProvider;

final class FamilyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
