<?php

declare(strict_types=1);

use App\Data\Models\Guardian;
use App\Domains\Family\Jobs\CreateFamilyClaimJob;
use App\Enums\FamilyClaimStatusEnum;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A pending claim: an email that already has a login asks to own this family, until its owner accepts. */

it('opens a first-revision pending claim in the family\'s church, with the email normalised', function (): void {
    $family = familyAt(satelliteOf(church()));
    $guardian = Guardian::factory()->create(['family_id' => $family->id]);

    $claim = (new CreateFamilyClaimJob(family: $family, guardian: $guardian, email: '  Janice@Example.COM '))->handle();

    expect([$claim->church_id, $claim->family_id, $claim->guardian_id])->toBe([$family->church_id, $family->id, $guardian->id])
        ->and($claim->email_normalised)->toBe('janice@example.com')
        ->and($claim->revision)->toBe(1)
        ->and($claim->status)->toBe(FamilyClaimStatusEnum::Pending);
});
