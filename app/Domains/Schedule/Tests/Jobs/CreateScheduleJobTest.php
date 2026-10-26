<?php

declare(strict_types=1);

use App\Domains\Schedule\Jobs\CreateScheduleJob;
use App\Enums\WeekdayEnum;
use Illuminate\Database\UniqueConstraintViolationException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/* Adding a class at a satellite: a name, and an optional day and times. */

it('adds an active class at the satellite, in its church', function (): void {
    $satellite = satelliteOf(church());

    $class = (new CreateScheduleJob(satellite: $satellite, name: '1PM', weekday: WeekdayEnum::Saturday, startsAt: '13:00', endsAt: '15:00'))->handle()->fresh();

    expect([$class?->church_id, $class?->satellite_id, $class?->name, $class?->weekday, $class?->startsAt(), $class?->endsAt(), $class?->is_active])
        ->toBe([$satellite->church_id, $satellite->id, '1PM', WeekdayEnum::Saturday, '13:00', '15:00', true]);
});

it('adds a class with no day and no times', function (): void {
    $class = (new CreateScheduleJob(satellite: satelliteOf(church()), name: 'Special class', weekday: null, startsAt: null, endsAt: null))->handle()->fresh();

    expect([$class?->weekday, $class?->startsAt(), $class?->endsAt()])->toBe([null, null, null]);
});

it('refuses a name an active class at the satellite already has', function (): void {
    $satellite = satelliteOf(church());
    scheduleAt($satellite, ['name' => '1PM']);

    (new CreateScheduleJob(satellite: $satellite, name: '1PM', weekday: null, startsAt: null, endsAt: null))->handle();
})->throws(UniqueConstraintViolationException::class);
