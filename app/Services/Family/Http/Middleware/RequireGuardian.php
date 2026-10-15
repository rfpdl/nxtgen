<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Middleware;

use App\Data\Models\Guardian;
use App\Data\Models\Organization;
use App\Data\Models\User;
use App\Foundation\Support\RouteOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Family routes: the signed-in user must be a linked guardian of a family in THIS church (plan §3.1).
 * Anyone else gets 404, not 403 — the page does not confirm the church or family exists.
 * The family is put on the request so Features never look it up from user input.
 */
final class RequireGuardian
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = RouteOrganization::from($request);
        $user = $request->user();

        abort_unless($organization instanceof Organization && $user instanceof User, 404);

        $guardian = Guardian::query()
            ->with('family.organization')
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        abort_if($guardian === null, 404);

        $request->attributes->set('family', $guardian->family);

        return $next($request);
    }
}
