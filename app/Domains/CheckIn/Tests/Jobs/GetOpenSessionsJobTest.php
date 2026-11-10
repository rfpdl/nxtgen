<?php

declare(strict_types=1);

use App\Data\Models\Session;
use App\Domains\CheckIn\Jobs\GetOpenSessionsJob;
use Carbon\CarbonImmutable;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* The bar's dropdown on a class's page (Roy 2026-11-10): the satellite's Sessions open today, by start time. */

it('lists the satellite\'s Sessions open today, by start time, with their class', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $sevenPm = sessionOf(scheduleAt($satellite, ['name' => '7PM', 'starts_at' => '19:00']), '2026-10-31');
    $onePm = sessionOf(scheduleAt($satellite, ['name' => '1PM', 'starts_at' => '13:00']), '2026-10-31');
    sessionOf(scheduleAt($satellite, ['name' => 'Closed', 'starts_at' => '10:00']), '2026-10-31')->update(['closed_at' => now()]);
    sessionOf(scheduleAt($satellite, ['name' => 'Last week', 'starts_at' => '09:00']), '2026-10-24');
    sessionOf(scheduleAt($satellite, ['name' => 'Retired', 'starts_at' => '11:00', 'is_active' => false]), '2026-10-31');   // retired while open (review)
    sessionOf(scheduleAt(satelliteOf($church), ['name' => 'Elsewhere']), '2026-10-31');

    $open = (new GetOpenSessionsJob(satellite: $satellite, today: CarbonImmutable::parse('2026-10-31')))->handle();

    expect(array_map(fn (Session $s): int => $s->id, $open))->toBe([$onePm->id, $sevenPm->id])
        ->and($open[0]->relationLoaded('schedule'))->toBeTrue();
});
