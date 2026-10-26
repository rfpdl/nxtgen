<?php

declare(strict_types=1);

use App\Data\Models\Schedule;
use App\Domains\Schedule\Jobs\GetSchedulesBySatelliteJob;
use App\Enums\WeekdayEnum;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/* The active classes of the asked-for satellites, in one query, keyed by satellite and in the order of the week. */

/**
 * @param  array<int, list<Schedule>>  $bySatellite
 * @return array<int, list<string>>
 */
function scheduleNames(array $bySatellite): array
{
    return array_map(fn (array $schedules): array => array_map(fn (Schedule $s): string => $s->name, $schedules), $bySatellite);
}

it('lists each satellite\'s active classes by day, then time, then name — a class with no day last', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    scheduleAt($satellite, ['name' => 'Special class', 'weekday' => null, 'starts_at' => null, 'ends_at' => null]);
    scheduleAt($satellite, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']);
    scheduleAt($satellite, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);
    scheduleAt($satellite, ['name' => 'Midweek', 'weekday' => WeekdayEnum::Wednesday, 'starts_at' => '19:00', 'ends_at' => null]);

    $found = (new GetSchedulesBySatelliteJob(church: $church, satelliteIds: [$satellite->id]))->handle();

    expect(scheduleNames($found))->toBe([$satellite->id => ['Midweek', '1PM', '7PM', 'Special class']]);
});

it('keys several satellites in one go — one with no classes is an empty list', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    scheduleAt($dxb, ['name' => '1PM']);
    scheduleAt($house, ['name' => '10AM']);

    $empty = satelliteOf($church);
    $found = (new GetSchedulesBySatelliteJob(church: $church, satelliteIds: [$dxb->id, $house->id, $empty->id]))->handle();

    expect(scheduleNames($found))->toBe([$dxb->id => ['1PM'], $house->id => ['10AM'], $empty->id => []]);
});

it('leaves out retired classes, satellites not asked for and another church\'s classes', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    scheduleAt($satellite, ['name' => '1PM']);
    scheduleAt($satellite, ['name' => 'Old class', 'is_active' => false]);
    scheduleAt(satelliteOf($church), ['name' => 'Not asked for']);
    $theirs = satelliteOf(church());
    scheduleAt($theirs, ['name' => 'Theirs']);

    $found = (new GetSchedulesBySatelliteJob(church: $church, satelliteIds: [$satellite->id, $theirs->id]))->handle();

    expect(scheduleNames($found))->toBe([$satellite->id => ['1PM'], $theirs->id => []]);
});
