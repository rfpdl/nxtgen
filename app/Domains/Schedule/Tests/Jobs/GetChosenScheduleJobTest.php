<?php

declare(strict_types=1);

use App\Domains\Schedule\Jobs\GetChosenScheduleJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/* The door's class picker (both tabs): the class `?class=` names among today's offered, else the first, else none. */

it('picks the class the address names, among today\'s', function (): void {
    $satellite = satelliteOf(church());
    $onePm = scheduleAt($satellite, ['name' => '1PM']);
    $sevenPm = scheduleAt($satellite, ['name' => '7PM']);

    expect((new GetChosenScheduleJob(classes: [$onePm, $sevenPm], id: $sevenPm->id))->handle()?->name)->toBe('7PM');
});

it('picks the first of today\'s classes when the address names none, and none when no class meets', function (): void {
    $satellite = satelliteOf(church());
    $onePm = scheduleAt($satellite, ['name' => '1PM']);

    expect((new GetChosenScheduleJob(classes: [$onePm, scheduleAt($satellite)], id: null))->handle()?->name)->toBe('1PM')
        ->and((new GetChosenScheduleJob(classes: [], id: null))->handle())->toBeNull();
});

it('does not find a class that is not among today\'s offered', function (): void {
    $satellite = satelliteOf(church());
    $other = scheduleAt($satellite);

    (new GetChosenScheduleJob(classes: [scheduleAt($satellite)], id: $other->id))->handle();
})->throws(ModelNotFoundException::class);
