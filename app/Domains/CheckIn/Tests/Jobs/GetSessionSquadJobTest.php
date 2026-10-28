<?php

declare(strict_types=1);

use App\Data\Models\Satellite;
use App\Domains\CheckIn\Jobs\GetSessionSquadJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* The Squads a head count can be confirmed for: those with a child checked in to the Session now. */

it('finds a Squad a child is checked in to at the Session', function (): void {
    $dxb = Satellite::factory()->for(church())->withSquads()->create();
    $session = sessionOf(scheduleAt($dxb));
    $in = checkedIn($session, childOf(familyAt($dxb), ['level' => 2, 'gender' => 'girl']));

    expect((new GetSessionSquadJob(session: $session, id: $in->squad_id))->handle()->name())->toBe('Level 2 Girls');
});

it('does not find a Squad with no one in now — its only child undone, or none ever', function (string $why): void {
    $dxb = Satellite::factory()->for(church())->withSquads()->create();
    $session = sessionOf(scheduleAt($dxb));
    $child = childOf(familyAt($dxb), ['level' => 2, 'gender' => 'girl']);
    $squadId = $why === 'undone'
        ? checkedIn($session, $child, ['undone_at' => now()])->squad_id
        : checkedIn(sessionOf($session->schedule, '2026-11-07'), $child)->squad_id;

    (new GetSessionSquadJob(session: $session, id: $squadId))->handle();
})->with(['undone', 'another Session'])->throws(ModelNotFoundException::class);
