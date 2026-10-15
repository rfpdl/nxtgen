<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Foundation\Support\RouteOrganization;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Browser-test support (the email mailbox file). Never outside APP_ENV=e2e.
        if ($this->app->environment('e2e')) {
            $this->app->register(E2EServiceProvider::class);
        }
    }

    public function boot(): void
    {
        // Lazy loading is a bug here, not a convenience: it is how a door list of 250 children turns
        // into 250 queries on church wifi. Fail loudly outside production.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Short, stable names in polymorphic columns (notes.noteable_type), never class names — a class rename
        // must not orphan rows. Enforced: an unmapped model in a morph relation is an error, not a silent FQCN.
        Relation::enforceMorphMap(['family' => Family::class, 'child' => Child::class]);

        // Every route starting with a church slug shares one rule, so /up, /login… never reach a church.
        Route::pattern('organization', RouteOrganization::pattern());

        // Public forms and emailed links: one per-device limit, from config (config/nxtgen.php).
        RateLimiter::for('per-device', fn (Request $request): Limit => Limit::perMinute(self::perDevicePerMinute())->by('device:'.$request->ip()));
    }

    public static function perDevicePerMinute(): int
    {
        $limit = config('nxtgen.rate_limits.per_device_per_minute');

        return is_int($limit) ? $limit : 20;
    }
}
