<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\GetFamilyChildJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A child by id, only within this family — a parent never reaches another family's child. */

it('finds the family\'s own child', function (): void {
    $family = familyAt(satelliteOf(church()));
    $child = childOf($family);

    expect((new GetFamilyChildJob(family: $family, id: $child->id))->handle()->id)->toBe($child->id);
});

it('refuses another family\'s child in the same satellite', function (): void {
    $satellite = satelliteOf(church());
    $neighbour = childOf(familyAt($satellite));

    (new GetFamilyChildJob(family: familyAt($satellite), id: $neighbour->id))->handle();
})->throws(ModelNotFoundException::class);

it('refuses another church\'s child', function (): void {
    $other = childOf(familyAt(satelliteOf(church())));

    (new GetFamilyChildJob(family: familyAt(satelliteOf(church())), id: $other->id))->handle();
})->throws(ModelNotFoundException::class);
