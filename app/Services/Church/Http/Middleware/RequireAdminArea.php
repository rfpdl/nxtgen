<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Middleware;

use App\Domains\Church\DataObjects\AccessScope;
use App\Foundation\Support\RequestContext;
use App\Foundation\Support\RouteSatellite;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Admin area (`/{satellite}/admin`, spec item 10): only someone with an admin Role at this satellite. Someone who
 * only serves here (a Lifeshaper) is sent to this satellite's Serve; RequireAssignment already gave everyone else a
 * 404. `:class` — Classes and Attendance — lets a class admin in too (TODO #9). Each page still asks its own row.
 */
final class RequireAdminArea
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, ?string $level = null): Response
    {
        $scope = RequestContext::scope($request);
        abort_unless($scope instanceof AccessScope, 404);

        if (! ($level === 'class' ? $scope->canUseAdmin() : $scope->canUseFullAdmin())) {
            // A GET is a page to open: send them to Serve. Anything else is an act they may not do here.
            abort_unless($request->isMethod('GET'), 404);

            return redirect()->route('serve.door.show', RouteSatellite::from($request));
        }

        return $next($request);
    }
}
