<?php

declare(strict_types=1);

use App\Data\Models\FamilyClaim;
use App\Domains\Family\Jobs\UpdateFamilyClaimStatusJob;
use App\Enums\FamilyClaimStatusEnum;
use Illuminate\Support\Carbon;

/* A family claim's status, and when it was resolved — a claim back at pending is unresolved again. */

it('sets the status and stamps when it was resolved', function (FamilyClaimStatusEnum $status): void {
    Carbon::setTestNow('2026-10-17 09:00:00');
    $claim = FamilyClaim::factory()->create();
    $other = FamilyClaim::factory()->create();

    (new UpdateFamilyClaimStatusJob(claim: $claim, status: $status))->handle();

    $claim->refresh();
    $other->refresh();
    expect($claim->status)->toBe($status)
        ->and($claim->resolved_at?->toDateTimeString())->toBe('2026-10-17 09:00:00')
        ->and($claim->revision)->toBe(1)
        ->and($other->status)->toBe(FamilyClaimStatusEnum::Pending);
})->with([FamilyClaimStatusEnum::Accepted, FamilyClaimStatusEnum::Revoked]);

it('clears the resolved time when set back to pending', function (): void {
    $claim = FamilyClaim::factory()->create(['status' => FamilyClaimStatusEnum::Revoked, 'resolved_at' => now()]);

    (new UpdateFamilyClaimStatusJob(claim: $claim, status: FamilyClaimStatusEnum::Pending))->handle();

    $claim->refresh();
    expect($claim->status)->toBe(FamilyClaimStatusEnum::Pending)->and($claim->resolved_at)->toBeNull();
});
