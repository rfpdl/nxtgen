<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Middleware;

use App\Data\Models\User;
use App\Domains\Family\Jobs\GetOwnGuardianJob;
use App\Foundation\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Lucid\Bus\UnitDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * Family routes (`/family`): the signed-in user must be a linked guardian of a family (plan §3.1). Anyone else gets
 * 404, not 403 — the page does not confirm the family exists.
 * The church and the guardian (and so their family) are put on the request so Features never look them up from user
 * input.
 */
final class RequireGuardian
{
    use UnitDispatcher;

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        $guardian = $this->run(new GetOwnGuardianJob(user: $user));
        abort_if($guardian === null, 404);

        RequestContext::putChurch($request, $guardian->family->church);
        RequestContext::putGuardian($request, $guardian);

        return $next($request);
    }
}
