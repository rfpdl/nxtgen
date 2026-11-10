<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Middleware;

use App\Data\Models\Session;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Foundation\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Lucid\Bus\UnitDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Session's own address (`/{session}/…` — its code, spec item 16), the one staff address without `{satellite}`
 * first (AddressRuleTest): a Role at the Session's satellite that covers its class (AccessScope::canCheckInTo — a
 * Lifeshaper or admin there, or that class's class admin), else 404. Then the request carries that satellite, its
 * church and the scope, as RequireAssignment's do — so the bar and the menu are that satellite's.
 */
final class RequireSessionAssignment
{
    use UnitDispatcher;

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->route('session');
        $user = $request->user();
        abort_unless($session instanceof Session && $user instanceof User, 404);

        $satellite = $session->satellite;
        $scope = $this->run(new GetAccessScopeJob(user: $user, satellite: $satellite));
        abort_unless($scope->canCheckInTo($session->schedule), 404);

        RequestContext::putSatellite($request, $satellite);
        RequestContext::putChurch($request, $satellite->church);
        RequestContext::putScope($request, $scope);

        return $next($request);
    }
}
