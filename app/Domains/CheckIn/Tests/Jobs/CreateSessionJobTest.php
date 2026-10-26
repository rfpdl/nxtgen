<?php

declare(strict_types=1);

use App\Data\Models\Session;
use App\Domains\CheckIn\Jobs\CreateSessionJob;
use Carbon\CarbonImmutable;

use function Tests\Support\atTheSameTime;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\withCommittedWorld;

/*
 * A Session is never opened: the first Check-in makes it (DECISIONS 2026-10-26) — one per class per date, even when
 * two phones check in the first children of the day at the same moment.
 */

it('makes the class\'s Session for the date, at the class\'s satellite', function (): void {
    $class = scheduleAt(satelliteOf(church()));

    $session = (new CreateSessionJob(schedule: $class, date: CarbonImmutable::parse('2026-10-31')))->handle();

    expect([$session->church_id, $session->satellite_id, $session->schedule_id, $session->date->toDateString()])
        ->toBe([$class->church_id, $class->satellite_id, $class->id, '2026-10-31']);
});

it('gives back the Session already there, and makes another only for another date or class', function (): void {
    $satellite = satelliteOf(church());
    $onePm = scheduleAt($satellite, ['name' => '1PM']);
    $existing = sessionOf($onePm, '2026-10-31');

    $again = (new CreateSessionJob(schedule: $onePm, date: CarbonImmutable::parse('2026-10-31')))->handle();
    (new CreateSessionJob(schedule: $onePm, date: CarbonImmutable::parse('2026-11-07')))->handle();
    (new CreateSessionJob(schedule: scheduleAt($satellite, ['name' => '7PM']), date: CarbonImmutable::parse('2026-10-31')))->handle();

    expect($again->id)->toBe($existing->id)
        ->and(Session::query()->count())->toBe(3);
});

it('makes ONE Session when two phones check in the first children at the same moment', function (): void {
    $class = scheduleAt(satelliteOf(church()));

    withCommittedWorld(function () use ($class): void {
        $ids = atTheSameTime(
            fn (): int => (new CreateSessionJob(schedule: $class, date: CarbonImmutable::parse('2026-10-31')))->handle()->id,
            fn (): int => (new CreateSessionJob(schedule: $class, date: CarbonImmutable::parse('2026-10-31')))->handle()->id,
        );

        expect($ids[0])->toBe($ids[1])
            ->and(Session::query()->count())->toBe(1);
    });
});
