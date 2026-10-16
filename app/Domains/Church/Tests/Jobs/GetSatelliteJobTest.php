<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetSatelliteJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* A satellite by id from a request — found only inside this church. */

it('finds a satellite in this church', function (): void {
    $church = church();
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);

    expect((new GetSatelliteJob(church: $church, id: $dxb->id))->handle()->name)->toBe('CCF DXB');
});

it('does not find another church\'s satellite by its id', function (): void {
    $theirs = satelliteOf(church());

    (new GetSatelliteJob(church: church(), id: $theirs->id))->handle();
})->throws(ModelNotFoundException::class);
