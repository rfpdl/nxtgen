<?php

declare(strict_types=1);

namespace App\Services\Org\Providers;

use Illuminate\Support\ServiceProvider;

final class OrgServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
