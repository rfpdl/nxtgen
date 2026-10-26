<?php

declare(strict_types=1);

use App\Domains\CheckIn\Jobs\GetCheckInJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* A Check-in by id, ONLY within this church — an id from the address never reaches another church's. */

it('finds a Check-in of this church, with its class and child', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $in = checkedIn(sessionOf(scheduleAt($satellite, ['name' => '1PM'])), childOf(familyAt($satellite), ['level' => 1, 'nickname' => 'Ben']));

    $found = (new GetCheckInJob(church: $church, id: $in->id))->handle();

    expect([$found->id, $found->session->schedule->name, $found->session->satellite->id, $found->child->nickname])
        ->toBe([$in->id, '1PM', $satellite->id, 'Ben']);
});

it('finds no other church\'s Check-in', function (): void {
    $satellite = satelliteOf(church());
    $in = checkedIn(sessionOf(scheduleAt($satellite)), childOf(familyAt($satellite), ['level' => 1]));

    expect(fn () => (new GetCheckInJob(church: church(), id: $in->id))->handle())->toThrow(ModelNotFoundException::class);
});
