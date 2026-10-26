<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetChurchTodayJob;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\church;

/*
 * The door's "today" is the church's day, not the server's (UTC): at 01:00 on Saturday in Dubai it is still Friday in
 * UTC, and a Lifeshaper there must see Saturday's classes.
 */

it('is today in the church\'s timezone', function (): void {
    $church = church(['timezone' => 'Asia/Dubai']);
    travelTo(CarbonImmutable::parse('2026-10-30 21:30:00', 'UTC'));   // Saturday 01:30 in Dubai

    $today = (new GetChurchTodayJob(church: $church))->handle();

    expect($today->toDateString())->toBe('2026-10-31')
        ->and($today->isSaturday())->toBeTrue()
        ->and($today->format('H:i'))->toBe('00:00');
});

it('is the server\'s date in a church west of it', function (): void {
    $church = church(['timezone' => 'America/New_York']);
    travelTo(CarbonImmutable::parse('2026-10-31 02:00:00', 'UTC'));   // still Friday evening in New York

    expect((new GetChurchTodayJob(church: $church))->handle()->toDateString())->toBe('2026-10-30');
});

it('takes the browser tests\' fixed day when the e2e server sets one', function (): void {
    // Only E2EServiceProvider sets it, so a Saturday class shows at the door on any weekday a run happens.
    config(['nxtgen.door_today' => '2026-10-31']);

    expect((new GetChurchTodayJob(church: church()))->handle()->toDateString())->toBe('2026-10-31');
});
