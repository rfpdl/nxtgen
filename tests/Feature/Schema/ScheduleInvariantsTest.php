<?php

declare(strict_types=1);

use App\Data\Models\Schedule;
use App\Enums\WeekdayEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/*
 * A Satellite's Schedules (shown as "class"; spec item 14): weekly (a weekday) or one-time (one date), never both,
 * always with a start and an end after it. One ACTIVE class per name per Satellite. All held by the database. A
 * retired class keeps its name for the record, so a new one may take it again. MySQL treats NULLs as distinct, so
 * the key is on a generated `active_name` (the name while active, null once retired).
 */

it('refuses a second active class with the same name at a satellite', function (): void {
    $satellite = satelliteOf(church());
    scheduleAt($satellite, ['name' => '1PM']);

    expect(fn () => scheduleAt($satellite, ['name' => '1PM']))
        ->toThrow(QueryException::class, 'schedules_satellite_id_active_name_unique');
});

it('lets a retired class\'s name be used again, and the same name at another satellite', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    scheduleAt($satellite, ['name' => '1PM', 'is_active' => false]);
    scheduleAt($satellite, ['name' => '1PM', 'is_active' => false]);
    scheduleAt($satellite, ['name' => '1PM']);
    scheduleAt(satelliteOf($church), ['name' => '1PM']);

    expect(Schedule::query()->count())->toBe(4);
});

it('keeps a weekly class\'s weekday and a one-time class\'s date, with times as HH:MM', function (): void {
    $satellite = satelliteOf(church());

    $weekly = scheduleAt($satellite, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00'])->fresh();
    $party = scheduleAt($satellite, ['name' => 'Christmas party', 'weekday' => null, 'date' => '2026-12-24', 'starts_at' => '18:00', 'ends_at' => '20:00'])->fresh();

    expect([$weekly?->weekday, $weekly?->date, $weekly?->startsAt(), $weekly?->endsAt()])->toBe([WeekdayEnum::Saturday, null, '13:00', '15:00'])
        ->and([$party?->weekday, $party?->date?->toDateString(), $party?->startsAt(), $party?->endsAt()])->toBe([null, '2026-12-24', '18:00', '20:00']);
});

it('refuses a class that is both weekly and one-time, or neither', function (array $when): void {
    /** @var array<model-property<Schedule>, mixed> $class */
    $class = ['name' => 'Odd', ...$when];

    expect(fn () => scheduleAt(satelliteOf(church()), $class))
        ->toThrow(QueryException::class, 'schedules_weekly_or_one_time');
})->with([
    'both' => [['weekday' => WeekdayEnum::Saturday, 'date' => '2026-12-24']],
    'neither' => [['weekday' => null, 'date' => null]],
]);

it('refuses a class without both times, or ending before it starts', function (array $times, string $error): void {
    /** @var array<model-property<Schedule>, mixed> $class */
    $class = ['name' => 'Odd', ...$times];

    expect(fn () => scheduleAt(satelliteOf(church()), $class))->toThrow(QueryException::class, $error);
})->with([
    'no start' => [['starts_at' => null, 'ends_at' => '15:00'], 'starts_at'],
    'no end' => [['ends_at' => null], 'ends_at'],
    'end before start' => [['starts_at' => '15:00', 'ends_at' => '13:00'], 'schedules_ends_after_start'],
    'end at start' => [['starts_at' => '13:00', 'ends_at' => '13:00'], 'schedules_ends_after_start'],
]);

it('meets every week on its weekday, and a one-time class on its date only', function (): void {
    $satellite = satelliteOf(church());
    $saturday = CarbonImmutable::parse('2026-10-31');
    $party = scheduleAt($satellite, ['name' => 'Christmas party', 'weekday' => null, 'date' => '2026-12-24']);

    expect(scheduleAt($satellite, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday])->meetsOn($saturday))->toBeTrue()
        ->and(scheduleAt($satellite, ['name' => 'Sunday school', 'weekday' => WeekdayEnum::Sunday])->meetsOn($saturday))->toBeFalse()
        ->and(Schedule::query()->where('name', '1PM')->sole()->meetsOn($saturday->addWeeks(7)))->toBeTrue()
        ->and($party->meetsOn(CarbonImmutable::parse('2026-12-24')))->toBeTrue()
        ->and($party->meetsOn(CarbonImmutable::parse('2026-12-25')))->toBeFalse()
        ->and($party->meetsOn(CarbonImmutable::parse('2026-12-17')))->toBeFalse();
});

it('is past once a one-time class\'s date has gone; a weekly class never is', function (): void {
    $satellite = satelliteOf(church());
    $party = scheduleAt($satellite, ['name' => 'Christmas party', 'weekday' => null, 'date' => '2026-12-24']);

    expect($party->isPastOn(CarbonImmutable::parse('2026-12-24')))->toBeFalse()
        ->and($party->isPastOn(CarbonImmutable::parse('2026-12-25')))->toBeTrue()
        ->and(scheduleAt($satellite, ['name' => '1PM'])->isPastOn(CarbonImmutable::parse('2099-01-01')))->toBeFalse();
});
