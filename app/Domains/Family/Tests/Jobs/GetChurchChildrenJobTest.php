<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Domains\Family\Jobs\GetChurchChildrenJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Several children by id — the ones ticked at the door — ONLY within this church, any satellite. */

it('finds each asked-for child of this church, from any family and satellite, keyed by id', function (): void {
    $church = church();
    $amara = childOf(familyAt(satelliteOf($church)));
    $bea = childOf(familyAt(satelliteOf($church)));
    childOf(familyAt(satelliteOf($church)));

    $found = (new GetChurchChildrenJob(church: $church, ids: [$bea->id, $amara->id]))->handle();

    expect(array_map(fn (Child $c): int => $c->id, $found))->toBe([$bea->id => $bea->id, $amara->id => $amara->id]);
});

it('refuses the lot when one id is not this church\'s child', function (): void {
    $church = church();
    $ours = childOf(familyAt(satelliteOf($church)));
    $theirs = childOf(familyAt(satelliteOf(church())));

    expect(fn () => (new GetChurchChildrenJob(church: $church, ids: [$ours->id, $theirs->id]))->handle())->toThrow(ModelNotFoundException::class);
});
