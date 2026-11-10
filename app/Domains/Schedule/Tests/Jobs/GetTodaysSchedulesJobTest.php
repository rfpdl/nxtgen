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
 * Today's classes (DECISIONS 2026-10-26) at the satellites the viewer's Role covers — a weekly class on its weekday, a
 * one-time class on its date only (spec item 14); never a retired class or another church's.
 */

it('lists the classes meeting today at the asked-for satellites, in start-time order', function (): void {
    $church = church();
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']);
    scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00']);
    scheduleAt($dxb, ['name' => 'Halloween party', 'weekday' => null, 'date' => '2026-10-31', 'starts_at' => '16:00', 'ends_at' => '18:00']);
    scheduleAt($dxb, ['name' => 'True Life Day 1', 'weekday' => null, 'date' => '2026-11-02', 'starts_at' => '09:00', 'ends_at' => '12:00']);
    scheduleAt($dxb, ['name' => 'Sunday school', 'weekday' => WeekdayEnum::Sunday]);
    scheduleAt($dxb, ['name' => 'Old class', 'is_active' => false]);
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '10:00']);

    $saturday = CarbonImmutable::parse('2026-10-31');
    $names = fn (array $classes): array => collect($classes)->pluck('name')->all();

    expect($names((new GetTodaysSchedulesJob(church: $church, satelliteIds: [$dxb->id], today: $saturday))->handle()))
        ->toBe(['1PM', 'Halloween party', '7PM'])
        ->and($names((new GetTodaysSchedulesJob(church: $church, satelliteIds: [$dxb->id], today: CarbonImmutable::parse('2026-11-02')))->handle()))
        ->toBe(['True Life Day 1'])
        ->and($names((new GetTodaysSchedulesJob(church: $church, satelliteIds: [$dxb->id], today: $saturday->addWeek()))->handle()))
        ->toBe(['1PM', '7PM']);
});

it('lists by satellite, never another church\'s even when handed its satellite, and nothing for no satellite', function (): void {
    $church = church();
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    $theirs = satelliteOf(church());
    scheduleAt($house, ['name' => '10AM', 'starts_at' => '10:00']);
    scheduleAt($dxb, ['name' => '1PM', 'starts_at' => '13:00']);
    scheduleAt($theirs, ['name' => 'Theirs']);
    $saturday = CarbonImmutable::parse('2026-10-31');

    $classes = (new GetTodaysSchedulesJob(church: $church, satelliteIds: [$house->id, $dxb->id, $theirs->id], today: $saturday))->handle();

    expect(array_map(fn (Schedule $s): string => "{$s->satellite->name} {$s->name}", $classes))->toBe(['CCF DXB 1PM', 'House Church 10AM'])
        ->and((new GetTodaysSchedulesJob(church: $church, satelliteIds: [], today: $saturday))->handle())->toBe([]);
});

it('adds a class admin\'s own classes to the satellites asked for — not their satellite\'s other classes (TODO #9)', function (): void {
    $church = church();
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House']);
    scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday]);
    $sevenPm = scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday]);
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Saturday]);

    $classes = (new GetTodaysSchedulesJob(church: $church, satelliteIds: [$house->id], today: CarbonImmutable::parse('2026-10-31'), alsoScheduleIds: [$sevenPm->id]))->handle();

    expect(array_map(fn (Schedule $s): string => $s->name, $classes))->toBe(['7PM', '10AM']);
});
