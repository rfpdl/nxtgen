<?php

declare(strict_types=1);

use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Data\Models\SquadHeadCount;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\GetSessionHeadCountsJob;

use function Tests\Support\church;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Each Squad's confirmed head count at one Session, by Squad id, with who confirmed it. */

it('gives each counted Squad\'s head count at this Session only, by Squad id, with its Squad and who', function (): void {
    $dxb = Satellite::factory()->for(church())->withSquads()->create();
    $session = sessionOf(scheduleAt($dxb));
    [$girls, $boys] = Squad::query()->where('satellite_id', $dxb->id)->where('level', 2)->orderBy('gender')->get()->all();
    $joy = User::factory()->create(['name' => 'Joy Santos']);
    SquadHeadCount::factory()->create(['session_id' => $session->id, 'squad_id' => $girls->id, 'head_count' => 4, 'counted_by' => $joy->id]);
    SquadHeadCount::factory()->create(['session_id' => sessionOf($session->schedule, '2026-11-07')->id, 'squad_id' => $boys->id]);

    $counts = (new GetSessionHeadCountsJob(session: $session))->handle();

    expect(array_keys($counts))->toBe([$girls->id])
        ->and([$counts[$girls->id]->head_count, $counts[$girls->id]->countedBy?->name, $counts[$girls->id]->squad->name()])->toBe([4, 'Joy Santos', $girls->name()])
        ->and($counts[$girls->id]->relationLoaded('squad'))->toBeTrue();
});
