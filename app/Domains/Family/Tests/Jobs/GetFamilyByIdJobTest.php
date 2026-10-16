<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\GetFamilyByIdJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A family by id, only within this church — an id from a URL never reaches another church. */

it('finds this church\'s family with its children, guardians and home satellite', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $family = familyAt($satellite);
    $child = childOf($family);

    $found = (new GetFamilyByIdJob(church: $church, id: $family->id))->handle();

    expect($found->id)->toBe($family->id)
        ->and($found->homeSatellite->id)->toBe($satellite->id)
        ->and($found->children->pluck('id')->all())->toBe([$child->id])
        ->and($found->relationLoaded('guardians'))->toBeTrue();
});

it('refuses another church\'s family', function (): void {
    $other = familyAt(satelliteOf(church()));

    (new GetFamilyByIdJob(church: church(), id: $other->id))->handle();
})->throws(ModelNotFoundException::class);
