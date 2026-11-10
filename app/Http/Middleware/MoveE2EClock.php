<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser-test support (TODO #13), added ONLY under APP_ENV=e2e (E2EServiceProvider): a request carrying
 * `X-E2E-Clock-Ahead: <minutes>` runs as if it were that much later, so a journey can open a link after it has
 * expired. Per request — the clock is put back as it was afterwards, and a journey that does not send it sees the real one.
 * The door's day (`nxtgen.door_today`, GetChurchTodayJob) moves by as much, so a journey can look at the day after.
 */
final class MoveE2EClock
{
    public const string HEADER = 'X-E2E-Clock-Ahead';

    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (string) $request->header(self::HEADER, '');
        if (! ctype_digit($minutes)) {
            /** @var Response */
            return $next($request);
        }

        $before = [Carbon::getTestNow(), CarbonImmutable::getTestNow()];
        $doorToday = config('nxtgen.door_today');
        $later = CarbonImmutable::now()->addMinutes((int) $minutes);
        Carbon::setTestNow($later);
        CarbonImmutable::setTestNow($later);
        if (is_string($doorToday) && $doorToday !== '') {
            config(['nxtgen.door_today' => CarbonImmutable::parse($doorToday)->addMinutes((int) $minutes)->toDateString()]);
        }

        try {
            /** @var Response */
            return $next($request);
        } finally {
            Carbon::setTestNow($before[0]);
            CarbonImmutable::setTestNow($before[1]);
            config(['nxtgen.door_today' => $doorToday]);
        }
    }
}
