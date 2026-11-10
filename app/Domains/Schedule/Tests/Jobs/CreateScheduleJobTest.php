<?php

declare(strict_types=1);

use App\Domains\Schedule\Jobs\CreateScheduleJob;
use App\Enums\WeekdayEnum;
use Illuminate\Database\UniqueConstraintViolationException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/* Adding a class at a satellite: a name, weekly on a day or one-time on a date, and its times. */

it('adds an active class at the satellite, in its church', function (): void {
    $satellite = satelliteOf(church());

    $class = (new CreateScheduleJob(satellite: $satellite, name: '1PM', weekday: WeekdayEnum::Saturday, date: null, startsAt: '13:00', endsAt: '15:00'))->handle()->fresh();

    expect([$class?->church_id, $class?->satellite_id, $class?->name, $class?->weekday, $class?->date, $class?->startsAt(), $class?->endsAt(), $class?->is_active])
        ->toBe([$satellite->church_id, $satellite->id, '1PM', WeekdayEnum::Saturday, null, '13:00', '15:00', true]);
});

it('adds a one-time class on its date', function (): void {
    $class = (new CreateScheduleJob(satellite: satelliteOf(church()), name: 'Christmas party', weekday: null, date: '2026-12-24', startsAt: '18:00', endsAt: '20:00'))->handle()->fresh();

    expect([$class?->weekday, $class?->date?->toDateString(), $class?->startsAt(), $class?->endsAt()])->toBe([null, '2026-12-24', '18:00', '20:00']);
});

it('refuses a name an active class at the satellite already has', function (): void {
    $satellite = satelliteOf(church());
    scheduleAt($satellite, ['name' => '1PM']);

    (new CreateScheduleJob(satellite: $satellite, name: '1PM', weekday: WeekdayEnum::Sunday, date: null, startsAt: '10:00', endsAt: '11:00'))->handle();
})->throws(UniqueConstraintViolationException::class);
