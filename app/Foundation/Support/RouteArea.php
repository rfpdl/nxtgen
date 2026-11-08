<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The staff area a request is in (Roy 2026-11-06, tasks/2026-11-06-admin-serve), read from its route name — which
 * follows the address: `admin.` (/{satellite}/admin) and `serve.` (/{satellite}/serve). A page shown in both areas
 * builds its links, and a Feature its redirect, in the area it is in, so neither leaves it.
 */
final class RouteArea
{
    public const array AREAS = ['admin', 'serve'];

    /** 'admin', 'serve', or null outside them. */
    public static function of(Request $request): ?string
    {
        $name = (string) $request->route()?->getName();

        foreach (self::AREAS as $area) {
            if (Str::startsWith($name, "{$area}.")) {
                return $area;
            }
        }

        return null;
    }

    /** The route `$name` (e.g. `families.show`) in this request's area — Serve outside one. */
    public static function name(Request $request, string $name): string
    {
        return (self::of($request) ?? 'serve').".{$name}";
    }
}
