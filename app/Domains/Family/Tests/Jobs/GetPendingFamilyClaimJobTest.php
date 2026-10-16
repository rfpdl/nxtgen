<?php

declare(strict_types=1);

use App\Data\Models\FamilyClaim;
use App\Domains\Family\Jobs\GetPendingFamilyClaimJob;
use App\Enums\FamilyClaimStatusEnum;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* This church's pending claim for an email, however the email is typed — never another church's. */

it('finds the pending claim for the email, whatever its case and spacing', function (): void {
    $church = church();
    $claim = FamilyClaim::factory()->create(['family_id' => familyAt(satelliteOf($church))->id, 'email_normalised' => 'maria@example.com']);

    expect((new GetPendingFamilyClaimJob(church: $church, email: '  Maria@Example.COM '))->handle()?->id)->toBe($claim->id);
});

it('does not find another church\'s claim for the same email', function (): void {
    FamilyClaim::factory()->create(['family_id' => familyAt(satelliteOf(church()))->id, 'email_normalised' => 'maria@example.com']);

    expect((new GetPendingFamilyClaimJob(church: church(), email: 'maria@example.com'))->handle())->toBeNull();
});

it('does not find a claim that is no longer pending', function (): void {
    $church = church();
    FamilyClaim::factory()->create([
        'family_id' => familyAt(satelliteOf($church))->id, 'email_normalised' => 'maria@example.com', 'status' => FamilyClaimStatusEnum::Accepted,
    ]);

    expect((new GetPendingFamilyClaimJob(church: $church, email: 'maria@example.com'))->handle())->toBeNull();
});
