<?php

declare(strict_types=1);

use App\Data\Models\FamilyClaim;
use App\Domains\Family\Jobs\GetPendingClaimForFamilyJob;
use App\Enums\FamilyClaimStatusEnum;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* The family's one claim still waiting for its email to be confirmed — not a resolved one, not another family's. */

it('finds the family\'s pending claim', function (): void {
    $family = familyAt(satelliteOf(church()));
    FamilyClaim::factory()->create(['family_id' => $family->id, 'status' => FamilyClaimStatusEnum::Revoked]);
    $pending = FamilyClaim::factory()->create(['family_id' => $family->id]);
    FamilyClaim::factory()->create();

    expect((new GetPendingClaimForFamilyJob(family: $family))->handle()?->id)->toBe($pending->id);
});

it('finds nothing once every claim of the family is resolved', function (): void {
    $family = familyAt(satelliteOf(church()));
    FamilyClaim::factory()->create(['family_id' => $family->id, 'status' => FamilyClaimStatusEnum::Revoked]);
    FamilyClaim::factory()->create();

    expect((new GetPendingClaimForFamilyJob(family: $family))->handle())->toBeNull();
});
