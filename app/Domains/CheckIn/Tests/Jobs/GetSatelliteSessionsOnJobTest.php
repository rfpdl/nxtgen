<?php

declare(strict_types=1);

use App\Data\Models\Session;
use App\Domains\CheckIn\Jobs\GetSatelliteSessionsOnJob;
use Carbon\CarbonImmutable;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* A satellite's Sessions on a date — open or closed — with their class: Serve lists each, whatever its class says now. */

it('finds the satellite\'s Sessions on the date, open or closed, of active classes, with their class', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $open = sessionOf(scheduleAt($satellite, ['name' => '1PM']), '2026-10-31');
    $closed = sessionOf(scheduleAt($satellite, ['name' => '7PM', 'starts_at' => '19:00']), '2026-10-31');
    $closed->update(['closed_at' => now()]);
    sessionOf(scheduleAt($satellite, ['name' => 'Retired', 'is_active' => false]), '2026-10-31');
    sessionOf(scheduleAt($satellite, ['name' => 'Last week']), '2026-10-24');
    sessionOf(scheduleAt(satelliteOf($church), ['name' => 'Elsewhere']), '2026-10-31');

    $found = (new GetSatelliteSessionsOnJob(satellite: $satellite, date: CarbonImmutable::parse('2026-10-31')))->handle();

    expect(array_map(fn (Session $s): int => $s->id, $found))->toEqualCanonicalizing([$open->id, $closed->id])
        ->and($found[0]->relationLoaded('schedule'))->toBeTrue();
});
