<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Domains\Card\Jobs\GetCardRequestJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A Card request by id, only within this church — an id from a URL never reaches another church's request. */

it('finds this church\'s request, with its child and family', function (): void {
    $church = church();
    $request = CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create();

    $found = (new GetCardRequestJob(church: $church, id: $request->id))->handle();

    expect($found->is($request))->toBeTrue()
        ->and($found->relationLoaded('child') && $found->child->relationLoaded('family'))->toBeTrue();
});

it('refuses another church\'s request', function (): void {
    $other = CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create();

    (new GetCardRequestJob(church: church(), id: $other->id))->handle();
})->throws(ModelNotFoundException::class);
