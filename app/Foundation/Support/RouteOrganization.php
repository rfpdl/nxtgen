<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Data\Models\Organization;
use Illuminate\Http\Request;

/**
 * The church a request is about (plan §3.1 "ResolveOrganization"), in ONE place — it was copied into both
 * service middlewares and the registration input (review-spar). Prefers what a middleware already resolved,
 * then the bound route model, then the raw `{organization}` slug (route-model binding may not have run yet).
 * Never reads the request body.
 */
final class RouteOrganization
{
    /**
     * Top-level paths that are NOT a church. The church slug sits at the start of the address (/ccf/families),
     * so without this `/up` (health check, registered after the services) was answered by the church
     * wildcard. A church can never take one of these as its slug.
     */
    public const array RESERVED = ['up', 'login', 'logout', 'home', 'magic', 'account', 'photos', 'build', 'storage'];

    /** The `{organization}` route pattern: a slug that is not reserved. */
    public static function pattern(): string
    {
        return '(?!(?:'.implode('|', self::RESERVED).')$)[a-z0-9][a-z0-9-]*';
    }

    public static function from(Request $request): ?Organization
    {
        $resolved = $request->attributes->get('organization') ?? $request->route('organization');

        if (is_string($resolved)) {
            $resolved = Organization::query()->where('slug', $resolved)->first();
        }

        return $resolved instanceof Organization ? $resolved : null;
    }
}
