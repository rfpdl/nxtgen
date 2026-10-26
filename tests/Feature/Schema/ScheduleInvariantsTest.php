<?php

declare(strict_types=1);

use App\Data\Models\Schedule;
use App\Enums\WeekdayEnum;
use Illuminate\Database\QueryException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/*
 * A Satellite's Schedules (shown as "class"): one ACTIVE class per name per Satellite — held by the database. A
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

it('keeps the day as a weekday and the times as HH:MM, or none of them', function (): void {
    $satellite = satelliteOf(church());

    $saturday = scheduleAt($satellite, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00'])->fresh();
    $anyDay = scheduleAt($satellite, ['name' => 'Special class', 'weekday' => null, 'starts_at' => null, 'ends_at' => null])->fresh();

    expect([$saturday?->weekday, $saturday?->startsAt(), $saturday?->endsAt()])->toBe([WeekdayEnum::Saturday, '13:00', '15:00'])
        ->and([$anyDay?->weekday, $anyDay?->startsAt(), $anyDay?->endsAt()])->toBe([null, null, null]);
});
