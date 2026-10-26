<?php

declare(strict_types=1);

use App\Domains\CheckIn\Jobs\GetSessionJob;
use Carbon\CarbonImmutable;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Looking at the door makes nothing: a class with nobody checked in yet has no Session. */

it('finds the class\'s Session on the date', function (): void {
    $class = scheduleAt(satelliteOf(church()));
    $session = sessionOf($class, '2026-10-31');
    sessionOf($class, '2026-11-07');

    expect((new GetSessionJob(schedule: $class, date: CarbonImmutable::parse('2026-10-31')))->handle()?->id)->toBe($session->id);
});

it('finds none before the first Check-in, nor another class\'s', function (): void {
    $satellite = satelliteOf(church());
    $onePm = scheduleAt($satellite, ['name' => '1PM']);
    sessionOf(scheduleAt($satellite, ['name' => '7PM']), '2026-10-31');

    expect((new GetSessionJob(schedule: $onePm, date: CarbonImmutable::parse('2026-10-31')))->handle())->toBeNull();
});
