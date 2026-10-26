<?php

declare(strict_types=1);

use App\Data\Models\Schedule;
use App\Domains\CheckIn\Jobs\GetReportableSchedulesJob;
use App\Enums\WeekdayEnum;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* The classes Attendance can report on: every active class, and a retired one that has a Session — its history stays. */

it('lists each satellite\'s active classes in week order, then its retired classes that have a Session', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00']);
    scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00']);
    sessionOf(scheduleAt($dxb, ['name' => 'Old class', 'is_active' => false]), '2026-10-17');
    scheduleAt($dxb, ['name' => 'Never met', 'is_active' => false]);
    scheduleAt(satelliteOf(church()), ['name' => 'Elsewhere']);

    $schedules = (new GetReportableSchedulesJob(church: $church, satelliteIds: [$dxb->id, $house->id]))->handle();

    expect(array_map(fn (Schedule $s): string => $s->name, $schedules[$dxb->id]))->toBe(['1PM', '7PM', 'Old class'])
        ->and($schedules[$house->id])->toBe([]);
});
