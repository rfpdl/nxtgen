<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Middleware;

use App\Data\Models\Church;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Foundation\Support\RouteChurch;
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
        $church = RouteChurch::from($request);
        $user = $request->user();

        abort_unless($church instanceof Church && $user instanceof User, 404);

        $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();
        abort_unless($scope->canWorkHere(), 404);

        $request->attributes->set('church', $church);
        $request->attributes->set('scope', $scope);
        // What the staff frame shows (ChurchShell). Navigation only — every Feature still asks the scope itself.
        Inertia::share('churchNav', [
            'canManagePeople' => $scope->canManagePeople(),
            'canReviewDuplicates' => $scope->canReviewDuplicates(),
        ]);

        return $next($request);
    }
}
