<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Middleware;

use App\Data\Models\Church;
use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Foundation\Support\RouteChurch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Family routes: the signed-in user must be a linked guardian of a family in THIS church (plan §3.1).
 * Anyone else gets 404, not 403 — the page does not confirm the church or family exists.
 * The church and the family are put on the request so Features never look them up from user input.
 */
final class RequireGuardian
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $church = RouteChurch::from($request);
        $user = $request->user();

        abort_unless($church instanceof Church && $user instanceof User, 404);

        $guardian = Guardian::query()
            ->with('family.church')
            ->where('church_id', $church->id)
            ->where('user_id', $user->id)
            ->first();

        abort_if($guardian === null, 404);

        $request->attributes->set('church', $church);
        $request->attributes->set('family', $guardian->family);

        return $next($request);
    }
}
