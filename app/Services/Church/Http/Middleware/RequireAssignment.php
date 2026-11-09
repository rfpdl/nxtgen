<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Middleware;

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Domains\User\Jobs\UpdateUserLastSatelliteJob;
use App\Foundation\Support\RequestContext;
use App\Foundation\Support\RouteSatellite;
use Closure;
use Illuminate\Http\Request;
use Lucid\Bus\UnitDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff routes (`/{satellite}/…`): at least one active Role at THE SATELLITE IN THE ADDRESS (spec item 10). Anyone
 * else gets 404. The satellite, its church and the AccessScope there are put on the request so Features ask the scope
 * and never compare roles themselves — and `{satellite}` is taken off the route, so a controller gets only the models
 * it acts on (`show(Family $family)`).
 */
final class RequireAssignment
{
    use UnitDispatcher;

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $satellite = RouteSatellite::from($request);
        $user = $request->user();

        abort_unless($satellite !== null && $user instanceof User, 404);

        $scope = $this->run(new GetAccessScopeJob(user: $user, satellite: $satellite));
        abort_unless($scope->canWorkHere(), 404);

        RequestContext::putSatellite($request, $satellite);
        RequestContext::putChurch($request, $satellite->church);
        // The staff menu (HandleInertiaRequests' `church_nav`) is read from here too — never Inertia::share, which
        // is process-wide and would outlive this request.
        RequestContext::putScope($request, $scope);
        // Where they land next time they sign in (spec item 11) — written only when it changes.
        if ($user->last_satellite_id !== $satellite->id) {
            $this->run(new UpdateUserLastSatelliteJob(user: $user, satellite: $satellite));
        }
        $request->route()?->forgetParameter('satellite');

        return $next($request);
    }
}
