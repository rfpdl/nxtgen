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
use Inertia\Inertia;
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
        RequestContext::putScope($request, $scope);
        // What the staff frame shows (ChurchShell). Navigation only — every Feature still asks the scope itself.
        Inertia::share('churchNav', [
            'can_manage_people' => $scope->canManagePeople(),
            'can_review_duplicates' => $scope->canReviewDuplicates(),
        ]);

        return $next($request);
    }
}
