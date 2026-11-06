<?php

declare(strict_types=1);

use App\Domains\Schedule\Jobs\GetSchedulesByIdJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/* Classes by id, keyed by id, with their satellite — retired ones too (a class admin's Role outlives its class) — this church's only. */

it('finds this church\'s classes by id, retired ones too, keyed by id', function (): void {
    $church = church();
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $onePm = scheduleAt($dxb, ['name' => '1PM']);
    $retired = scheduleAt($dxb, ['name' => 'Old', 'is_active' => false]);
    $theirs = scheduleAt(satelliteOf(church()));

    $found = (new GetSchedulesByIdJob(church: $church, ids: [$onePm->id, $retired->id, $theirs->id]))->handle();

    expect(array_keys($found))->toEqualCanonicalizing([$onePm->id, $retired->id])
        ->and($found[$onePm->id]->satellite->name)->toBe('CCF DXB');
});

it('asks nothing for no ids', function (): void {
    expect((new GetSchedulesByIdJob(church: church(), ids: []))->handle())->toBe([]);
});
