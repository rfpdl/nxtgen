<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\GetChurchChildJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A child by id, only within this church — an id from a URL never reaches another church's child. */

it('finds this church\'s child, with its family', function (): void {
    $church = church();
    $child = childOf(familyAt(satelliteOf($church)));

    $found = (new GetChurchChildJob(church: $church, id: $child->id))->handle();

    expect($found->id)->toBe($child->id)
        ->and($found->relationLoaded('family'))->toBeTrue();
});

it('refuses another church\'s child', function (): void {
    $other = childOf(familyAt(satelliteOf(church())));

    (new GetChurchChildJob(church: church(), id: $other->id))->handle();
})->throws(ModelNotFoundException::class);
