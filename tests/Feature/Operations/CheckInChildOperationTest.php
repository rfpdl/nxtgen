<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Enums\CheckInResultEnum;
use App\Operations\CheckIn\CheckInChildOperation;
use Carbon\CarbonImmutable;

use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\withCommittedWorld;

/*
 * Two phones checking the same child in at the same moment, through the WHOLE Operation — its transaction included
 * (MySQL REPEATABLE READ: a plain read inside it does not see the other phone's new row). Both phones must hear
 * "checked in" or "already checked in", never an error, and there is one Check-in, one Session, one Squad.
 */

/** @return list<string> both phones' results, sorted */
function bothPhones(Schedule $class, Child $child): array
{
    $saturday = CarbonImmutable::parse('2026-10-31');
    [$a, $b] = [User::factory()->create(), User::factory()->create()];
    $results = [];

    withCommittedWorld(function () use ($class, $child, $saturday, $a, $b, &$results): void {
        $results = atTheSameTime(
            fn (): string => (new CheckInChildOperation(schedule: $class, today: $saturday, child: $child, level: null, makeActive: false, by: $a))->handle()->value,
            fn (): string => (new CheckInChildOperation(schedule: $class, today: $saturday, child: $child, level: null, makeActive: false, by: $b))->handle()->value,
        );
        expect([CheckIn::query()->count(), Session::query()->count(), Squad::query()->where('satellite_id', $class->satellite_id)->where('level', 2)->where('gender', 'girl')->count()])
            ->toBe([1, 1, 1]);
    });

    $values = array_map(fn (mixed $r): string => is_string($r) ? $r : 'not a result', $results);
    sort($values);

    return $values;
}

it('answers both phones when the Session is already there', function (): void {
    $satellite = Satellite::factory()->for(church())->withSquads()->create();
    $class = scheduleAt($satellite);
    sessionOf($class, '2026-10-31');
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']);

    expect(bothPhones($class, $child))->toBe([CheckInResultEnum::AlreadyIn->value, CheckInResultEnum::CheckedIn->value]);
});

it('answers both phones when theirs is the first Check-in of the day, at a satellite missing the Squad', function (): void {
    $satellite = satelliteOf(church());
    $class = scheduleAt($satellite);
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']);

    expect(bothPhones($class, $child))->toBe([CheckInResultEnum::AlreadyIn->value, CheckInResultEnum::CheckedIn->value]);
});
