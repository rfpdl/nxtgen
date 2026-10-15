<?php

declare(strict_types=1);

namespace App\Services\Auth\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        // Per email AND per IP: one address cannot be flooded, and one device cannot spray addresses.
        RateLimiter::for('magic-link', function (Request $request): array {
            $email = $request->string('email')->trim()->lower()->value();

            return [
                Limit::perMinute(5)->by('email:'.$email),
                Limit::perMinute(20)->by('ip:'.$request->ip()),
            ];
        });
    }
}
