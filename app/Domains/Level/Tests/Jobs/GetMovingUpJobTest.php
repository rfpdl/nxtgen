<?php

declare(strict_types=1);

use App\Data\Models\MovingUp;
use App\Domains\Level\Jobs\GetMovingUpJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* A Moving-Up by the id in an address — found only inside this church. */

it('finds a Moving-Up of this church', function (): void {
    $church = church();
    $movingUp = MovingUp::query()->create(['church_id' => $church->id, 'satellite_id' => satelliteOf($church)->id]);

    expect((new GetMovingUpJob(church: $church, id: $movingUp->id))->handle()->id)->toBe($movingUp->id);
});

it('does not find another church\'s Moving-Up by its id', function (): void {
    $theirs = satelliteOf(church());
    $theirs = MovingUp::query()->create(['church_id' => $theirs->church_id, 'satellite_id' => $theirs->id]);

    (new GetMovingUpJob(church: church(), id: $theirs->id))->handle();
})->throws(ModelNotFoundException::class);
