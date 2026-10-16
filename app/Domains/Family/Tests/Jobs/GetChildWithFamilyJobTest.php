<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\GetChildWithFamilyJob;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A child by id with its family and church — the church is READ from the child, for the caller to check. */

it('finds the child in any church and loads its own family and church', function (): void {
    church();
    $church = church();
    $child = childOf(familyAt(satelliteOf($church)));

    $found = (new GetChildWithFamilyJob(id: $child->id))->handle();

    expect($found?->id)->toBe($child->id)
        ->and($found?->relationLoaded('family'))->toBeTrue()
        ->and($found?->family?->id)->toBe($child->family_id)
        ->and($found?->family?->church?->id)->toBe($church->id);
});

it('returns nothing for an unknown id', function (): void {
    expect((new GetChildWithFamilyJob(id: 999_999))->handle())->toBeNull();
});
