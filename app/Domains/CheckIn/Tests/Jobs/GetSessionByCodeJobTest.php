<?php

declare(strict_types=1);

use App\Domains\CheckIn\Jobs\GetSessionByCodeJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* A Session's address carries its code (spec item 16): the code finds the Session, with its class and satellite. */

it('finds the Session a code names, with its class, satellite and church', function (): void {
    $session = sessionOf(scheduleAt(satelliteOf(church()), ['name' => '1PM']));
    sessionOf(scheduleAt(satelliteOf(church()), ['name' => 'Elsewhere']));

    $found = (new GetSessionByCodeJob(code: $session->code))->handle();

    expect($found?->id)->toBe($session->id)
        ->and([$found?->relationLoaded('schedule'), $found?->relationLoaded('satellite'), $found?->schedule->name])->toBe([true, true, '1PM']);
});

it('finds nothing for a code no Session has, or one written differently', function (): void {
    $session = sessionOf(scheduleAt(satelliteOf(church())));

    expect((new GetSessionByCodeJob(code: 'zzzzzzzz'))->handle())->toBeNull()
        ->and((new GetSessionByCodeJob(code: mb_strtoupper($session->code)))->handle())->toBeNull();
});
