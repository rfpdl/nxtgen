<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Data\Models\Church;
use Illuminate\Http\Request;

/**
 * The church a request is about (plan §3.1), in ONE place — it was copied into both
 * service middlewares and the registration input (review-spar). Prefers what a middleware already resolved,
 * then the bound route model, then the raw `{church}` slug (route-model binding may not have run yet).
 * Never reads the request body.
 */
final class RouteChurch
{
    /**
     * Top-level paths that are NOT a church. The church slug sits at the start of the address (/ccf/families),
     * so without this `/up` (health check, registered after the services) was answered by the church
     * wildcard. A church can never take one of these as its slug.
     */
    public const array RESERVED = ['up', 'login', 'logout', 'home', 'magic', 'account', 'photos', 'build', 'storage'];

    /** The `{church}` route pattern: a slug that is not reserved. */
    public static function pattern(): string
    {
        return '(?!(?:'.implode('|', self::RESERVED).')$)[a-z0-9][a-z0-9-]*';
    }

    public static function from(Request $request): ?Church
    {
        $resolved = $request->attributes->get('church') ?? $request->route('church');

        if (is_string($resolved)) {
            $resolved = Church::query()->where('slug', $resolved)->first();
        }

        return $resolved instanceof Church ? $resolved : null;
    }
}
