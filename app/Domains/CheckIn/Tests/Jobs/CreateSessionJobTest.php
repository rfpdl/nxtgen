<?php

declare(strict_types=1);

use App\Data\Models\Session;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\CreateSessionJob;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\atTheSameTime;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\withCommittedWorld;

/*
 * An admin opening a class makes its Session for the date (spec item 15), open, saying who opened it — one per class
 * per date, even when two admins press Open at the same moment.
 */

it('makes the class\'s Session for the date, at the class\'s satellite, open and by whom', function (): void {
    travelTo(CarbonImmutable::parse('2026-10-31 08:00:00'));
    $class = scheduleAt(satelliteOf(church()));
    $admin = User::factory()->create();

    $session = (new CreateSessionJob(schedule: $class, date: CarbonImmutable::parse('2026-10-31'), by: $admin))->handle()->fresh();

    expect([$session?->church_id, $session?->satellite_id, $session?->schedule_id, $session?->date->toDateString()])
        ->toBe([$class->church_id, $class->satellite_id, $class->id, '2026-10-31'])
        ->and([$session?->opened_at->toDateTimeString(), $session?->opened_by, $session?->closed_at])->toBe(['2026-10-31 08:00:00', $admin->id, null]);
});

it('gives back the Session already there, and makes another only for another date or class', function (): void {
    $satellite = satelliteOf(church());
    $onePm = scheduleAt($satellite, ['name' => '1PM']);
    $existing = sessionOf($onePm, '2026-10-31');

    $again = (new CreateSessionJob(schedule: $onePm, date: CarbonImmutable::parse('2026-10-31'), by: null))->handle();
    (new CreateSessionJob(schedule: $onePm, date: CarbonImmutable::parse('2026-11-07'), by: null))->handle();
    (new CreateSessionJob(schedule: scheduleAt($satellite, ['name' => '7PM']), date: CarbonImmutable::parse('2026-10-31'), by: null))->handle();

    expect($again->id)->toBe($existing->id)
        ->and(Session::query()->count())->toBe(3);
});

it('makes ONE Session when two admins open the class at the same moment', function (): void {
    $class = scheduleAt(satelliteOf(church()));

    withCommittedWorld(function () use ($class): void {
        $ids = atTheSameTime(
            fn (): int => (new CreateSessionJob(schedule: $class, date: CarbonImmutable::parse('2026-10-31'), by: null))->handle()->id,
            fn (): int => (new CreateSessionJob(schedule: $class, date: CarbonImmutable::parse('2026-10-31'), by: null))->handle()->id,
        );

        expect($ids[0])->toBe($ids[1])
            ->and(Session::query()->count())->toBe(1);
    });
});
