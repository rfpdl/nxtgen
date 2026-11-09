<?php

declare(strict_types=1);

namespace App\Services\Platform\Http\Middleware;

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetChurchesJob;
use App\Foundation\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Lucid\Bus\UnitDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * `/platform` (spec item 12): only the platform admin, set at the terminal. For anyone else it is an address nobody
 * has (404), as for any page they may not open. The church the pages are about — the app serves one (CCF), the first
 * in the data — is put on the request, as the staff pages' is.
 */
final class RequirePlatformAdmin
{
    use UnitDispatcher;

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_platform_admin, 404);

        RequestContext::putChurch($request, $this->run(new GetChurchesJob)[0] ?? abort(404));

        return $next($request);
    }
}
