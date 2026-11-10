<?php

declare(strict_types=1);

use App\Domains\CheckIn\Jobs\GetSessionsOnJob;
use Carbon\CarbonImmutable;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Serve's first page asks once for every listed class's Session on the date — keyed by class; one not opened has none. */

it('finds each class\'s Session on the date, in one go, keyed by class', function (): void {
    $satellite = satelliteOf(church());
    $onePm = scheduleAt($satellite, ['name' => '1PM']);
    $sevenPm = scheduleAt($satellite, ['name' => '7PM', 'starts_at' => '19:00']);
    $notOpened = scheduleAt($satellite, ['name' => 'Late', 'starts_at' => '21:00']);
    $today = sessionOf($onePm, '2026-10-31');
    sessionOf($onePm, '2026-10-24');
    $seven = sessionOf($sevenPm, '2026-10-31');
    sessionOf(scheduleAt($satellite, ['name' => 'Not asked']), '2026-10-31');

    $found = (new GetSessionsOnJob(classes: [$onePm, $sevenPm, $notOpened], date: CarbonImmutable::parse('2026-10-31')))->handle();

    expect(array_map(fn ($s) => $s->id, $found))->toBe([$onePm->id => $today->id, $sevenPm->id => $seven->id]);
});

it('asks nothing for no classes', function (): void {
    expect((new GetSessionsOnJob(classes: [], date: CarbonImmutable::parse('2026-10-31')))->handle())->toBe([]);
});
