<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Middleware;

use App\Domains\Church\DataObjects\AccessScope;
use App\Foundation\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Admin areas (/{church}/admin, /{church}/nxtgen/admin — Roy 2026-11-06): only someone with an admin Role here,
 * church or satellite; anyone else gets 404, as for any staff page they may not open. `:class` — Classes and
 * Attendance — lets a class admin in too (TODO #9). Runs after RequireAssignment, which put the scope on the request.
 * Each page still asks its own row.
 */
final class RequireAdminArea
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, ?string $level = null): Response
    {
        $scope = RequestContext::scope($request);
        abort_unless($scope instanceof AccessScope && ($level === 'class' ? $scope->canUseAdmin() : $scope->canUseFullAdmin()), 404);

        return $next($request);
    }
}
