<?php

declare(strict_types=1);

use App\Http\Middleware\MoveE2EClock;
use App\Providers\E2EServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;
use function Pest\Laravel\withHeader;

/*
 * A clock the browser journeys can move (TODO #13): a link expires after 2 hours (sign in) or 7 days (confirm), and
 * a journey cannot wait that long. Under APP_ENV=e2e only, a request carrying `X-E2E-Clock-Ahead: <minutes>` runs as
 * if it were that much later. It is per REQUEST — one journey's browser sends it, no other journey's clock moves.
 */

beforeEach(function (): void {
    travelTo(CarbonImmutable::parse('2026-11-03 21:00:00'));
    Route::get('/__test/now', fn () => response()->json(['now' => now()->toDateTimeString(), 'immutable' => CarbonImmutable::now()->toDateTimeString()]));
});

it('runs a request that says so as if it were that many minutes later', function (): void {
    app()->register(E2EServiceProvider::class, force: true);

    withHeader(MoveE2EClock::HEADER, '121')->get('/__test/now')
        ->assertJson(['now' => '2026-11-03 23:01:00', 'immutable' => '2026-11-03 23:01:00']);

    // …and puts the clock back as it was once the request is done.
    expect(now()->toDateTimeString())->toBe('2026-11-03 21:00:00')
        ->and(CarbonImmutable::now()->toDateTimeString())->toBe('2026-11-03 21:00:00');
});

it('moves the door\'s day by as much — a one-time class is not offered the day after (issue 10)', function (): void {
    Route::get('/__test/door-today', fn () => response()->json(['day' => config('nxtgen.door_today')]));
    app()->register(E2EServiceProvider::class, force: true);
    config(['nxtgen.door_today' => '2026-11-07']);

    withHeader(MoveE2EClock::HEADER, '1440')->get('/__test/door-today')->assertJson(['day' => '2026-11-08']);

    // …and puts it back for the next request.
    expect(config('nxtgen.door_today'))->toBe('2026-11-07');
});

it('never sweeps away saved sign-ins, whatever a request\'s clock says', function (): void {
    // Laravel clears old sessions on a random request (the lottery), by that request's clock. A week ahead, every
    // staff member's saved sign-in looks a week old and goes — and every staff journey after it fails. An e2e
    // database is wiped every run, so it never needs clearing.
    config(['session.lottery' => [2, 100]]);

    app()->register(E2EServiceProvider::class, force: true);

    expect(config('session.lottery'))->toBe([0, 100]);
});

it('leaves the clock alone for a request that does not say so', function (): void {
    app()->register(E2EServiceProvider::class, force: true);

    get('/__test/now')->assertJson(['now' => '2026-11-03 21:00:00']);
});

it('moves nothing outside the e2e environment', function (): void {
    withHeader(MoveE2EClock::HEADER, '121')->get('/__test/now')->assertJson(['now' => '2026-11-03 21:00:00']);
});

it('ignores anything that is not a whole number of minutes ahead', function (string $value): void {
    app()->register(E2EServiceProvider::class, force: true);

    withHeader(MoveE2EClock::HEADER, $value)->get('/__test/now')->assertJson(['now' => '2026-11-03 21:00:00']);
})->with(['-5', 'soon', '1.5', '']);
