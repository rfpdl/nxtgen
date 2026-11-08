<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Data\Models\Satellite;
use Illuminate\Http\Request;

/**
 * The satellite a request is about (spec items 5–6, Roy 2026-11-07), in ONE place: the app is CCF's NxtGen only, so
 * the church stays in the data and the address names the satellite — `/dubai/serve/…`, `/dubai/register`. The church
 * is the satellite's. Prefers what a middleware already resolved (RequireAssignment takes `{satellite}` off a staff
 * route once it has checked it), then the bound route model (RouteBindingServiceProvider). Never reads the request
 * body.
 */
final class RouteSatellite
{
    /**
     * Top-level paths that are NOT a satellite. The satellite slug sits at the start of the address (/dubai/admin),
     * so without this `/up` (health check, registered after the services) was answered by the satellite wildcard. A
     * satellite can never take one of these as its slug.
     */
    public const array RESERVED = ['up', 'login', 'loginp', 'loginc', 'logout', 'home', 'magic', 'account', 'photos', 'build', 'storage', 'family', 'platform'];

    /** The `{satellite}` route pattern: a slug that is not reserved. */
    public static function pattern(): string
    {
        return '(?!(?:'.implode('|', self::RESERVED).')$)[a-z0-9][a-z0-9-]*';
    }

    public static function from(Request $request): ?Satellite
    {
        $resolved = RequestContext::satellite($request) ?? $request->route('satellite');

        return $resolved instanceof Satellite ? $resolved : null;
    }
}
