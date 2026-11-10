<?php

declare(strict_types=1);

use App\Domains\Schedule\Jobs\UpdateScheduleJob;
use App\Enums\WeekdayEnum;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/* Editing a class: its name, day or date, and times — never its satellite. */

it('changes the name, day and times', function (): void {
    $class = scheduleAt(satelliteOf(church()), ['name' => '1PM']);

    (new UpdateScheduleJob(schedule: $class, name: '1:30PM', weekday: WeekdayEnum::Sunday, date: null, startsAt: '13:30', endsAt: '14:30'))->handle();

    $saved = $class->fresh();
    expect([$saved?->name, $saved?->weekday, $saved?->startsAt(), $saved?->endsAt()])->toBe(['1:30PM', WeekdayEnum::Sunday, '13:30', '14:30']);
});

it('makes a weekly class one-time — the day goes', function (): void {
    $class = scheduleAt(satelliteOf(church()), ['name' => '1PM']);

    (new UpdateScheduleJob(schedule: $class, name: '1PM', weekday: null, date: '2026-12-26', startsAt: '13:00', endsAt: '15:00'))->handle();

    expect([$class->fresh()?->weekday, $class->fresh()?->date?->toDateString()])->toBe([null, '2026-12-26']);
});

it('leaves the satellite\'s other classes alone', function (): void {
    $satellite = satelliteOf(church());
    $class = scheduleAt($satellite, ['name' => '1PM']);
    $other = scheduleAt($satellite, ['name' => '7PM', 'starts_at' => '19:00', 'ends_at' => '21:00']);

    (new UpdateScheduleJob(schedule: $class, name: '1PM', weekday: WeekdayEnum::Sunday, date: null, startsAt: '10:00', endsAt: '11:00'))->handle();

    expect([$other->fresh()?->name, $other->fresh()?->startsAt()])->toBe(['7PM', '19:00']);
});
