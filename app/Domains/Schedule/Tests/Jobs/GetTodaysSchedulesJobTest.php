<?php

declare(strict_types=1);

use App\Data\Models\Schedule;
use App\Domains\Schedule\Jobs\GetTodaysSchedulesJob;
use App\Enums\WeekdayEnum;
use Carbon\CarbonImmutable;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/*
 * The door's class picker (DECISIONS 2026-10-26): today's classes at the satellites the viewer's Role covers — a class
 * with a day only on that day, one with no day every day; never a retired class or another church's.
 */

it('lists the classes meeting today at the asked-for satellites, in start-time order', function (): void {
    $church = church();
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00']);
    scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00']);
    scheduleAt($dxb, ['name' => 'Special class', 'weekday' => null, 'starts_at' => null]);
    scheduleAt($dxb, ['name' => 'Sunday school', 'weekday' => WeekdayEnum::Sunday]);
    scheduleAt($dxb, ['name' => 'Old class', 'weekday' => null, 'is_active' => false]);
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '10:00']);

    $saturday = CarbonImmutable::parse('2026-10-31');
    $names = fn (array $classes): array => collect($classes)->pluck('name')->all();

    expect($names((new GetTodaysSchedulesJob(church: $church, satelliteIds: [$dxb->id], today: $saturday))->handle()))
        ->toBe(['1PM', '7PM', 'Special class'])
        ->and($names((new GetTodaysSchedulesJob(church: $church, satelliteIds: [$dxb->id], today: CarbonImmutable::parse('2026-11-02')))->handle()))
        ->toBe(['Special class']);
});

it('lists every satellite\'s for a church-wide Role, by satellite, and never another church\'s', function (): void {
    $church = church();
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    scheduleAt($house, ['name' => '10AM', 'starts_at' => '10:00']);
    scheduleAt($dxb, ['name' => '1PM', 'starts_at' => '13:00']);
    scheduleAt(satelliteOf(church()), ['name' => 'Theirs']);

    $classes = (new GetTodaysSchedulesJob(church: $church, satelliteIds: null, today: CarbonImmutable::parse('2026-10-31')))->handle();

    expect(array_map(fn (Schedule $s): string => "{$s->satellite->name} {$s->name}", $classes))->toBe(['CCF DXB 1PM', 'House Church 10AM']);
});
