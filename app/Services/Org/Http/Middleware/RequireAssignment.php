<?php

declare(strict_types=1);

namespace App\Services\Org\Http\Middleware;

use App\Data\Models\Organization;
use App\Data\Models\User;
use App\Domains\Organization\Jobs\GetAccessScopeJob;
use App\Foundation\Support\RouteOrganization;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff routes: at least one active role in THIS church (plan §3.1). Anyone else gets 404. The church and
 * the AccessScope are put on the request so Features ask the scope and never compare roles themselves.
 */
final class RequireAssignment
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = RouteOrganization::from($request);
        $user = $request->user();

        abort_unless($organization instanceof Organization && $user instanceof User, 404);

        $scope = (new GetAccessScopeJob(user: $user, organization: $organization))->handle();
        abort_unless($scope->canWorkHere(), 404);

        $request->attributes->set('organization', $organization);
        $request->attributes->set('scope', $scope);
        // What the staff frame shows (OrgShell). Navigation only — every Feature still asks the scope itself.
        Inertia::share('orgNav', [
            'canManagePeople' => $scope->canManagePeople(),
            'canReviewDuplicates' => $scope->canReviewDuplicates(),
        ]);

        return $next($request);
    }
}
