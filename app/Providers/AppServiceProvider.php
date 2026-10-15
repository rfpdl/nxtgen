<?php

declare(strict_types=1);

namespace App\Providers;

use App\Foundation\Support\RouteOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Lazy loading is a bug here, not a convenience: it is how a door list of 250 children turns
        // into 250 queries on church wifi. Fail loudly outside production.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Every route starting with a church slug shares one rule, so /up, /login… never reach a church.
        Route::pattern('organization', RouteOrganization::pattern());
    }
}
