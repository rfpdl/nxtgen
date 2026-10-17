<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Middleware;

use App\Data\Models\Church;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Foundation\Support\RequestContext;
use App\Foundation\Support\RouteChurch;
use Closure;
use Illuminate\Http\Request;
use Lucid\Bus\UnitDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff routes: at least one active role in THIS church (plan §3.1). Anyone else gets 404. The church and
 * the AccessScope are put on the request so Features ask the scope and never compare roles themselves.
 */
final class RequireAssignment
{
    use UnitDispatcher;

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $church = RouteChurch::from($request);
        $user = $request->user();

        abort_unless($church instanceof Church && $user instanceof User, 404);

        $scope = $this->run(new GetAccessScopeJob(user: $user, church: $church));
        abort_unless($scope->canWorkHere(), 404);

        RequestContext::putChurch($request, $church);
        // The staff menu (HandleInertiaRequests' `church_nav`) is read from here too — never Inertia::share, which
        // is process-wide and would outlive this request.
        RequestContext::putScope($request, $scope);

        return $next($request);
    }
}
