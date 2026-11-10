<?php

declare(strict_types=1);

use App\Domains\CheckIn\Jobs\GetSessionsByIdJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Sessions named by id — a helper's Role (spec item 18) — within one church only, with their class and satellite. */

it('finds the asked-for Sessions of this church, keyed by id, with their class and satellite', function (): void {
    $church = church();
    $mine = sessionOf(scheduleAt(satelliteOf($church), ['name' => '1PM']));
    $theirs = sessionOf(scheduleAt(satelliteOf(church())));

    $found = (new GetSessionsByIdJob(church: $church, ids: [$mine->id, $theirs->id]))->handle();

    expect(array_keys($found))->toBe([$mine->id])
        ->and([$found[$mine->id]->relationLoaded('schedule'), $found[$mine->id]->relationLoaded('satellite'), $found[$mine->id]->schedule->name])->toBe([true, true, '1PM'])
        ->and((new GetSessionsByIdJob(church: $church, ids: []))->handle())->toBe([]);
});
