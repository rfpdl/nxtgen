<?php

declare(strict_types=1);

use App\Data\Models\FamilyClaim;
use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Enums\FamilyClaimStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Operations\Auth\AcceptFamilyClaimOperation;

use function Tests\Support\atTheSameTime;
use function Tests\Support\withCommittedWorld;

/*
 * A parent taps the claim link twice (two tabs, a double tap) and both arrive at the same moment, each on its own
 * connection (TODO #7). The link is single use, so exactly one of them accepts the claim: one login, the guardian
 * linked once, the claim Accepted — and the other tap is told the link is no longer valid, never an error.
 * The cache lock is per process in tests (array store), so this proves the database alone holds. It holds in depth:
 * with the link's single-use and Pending checks both removed, the second tap still rolls back at the email-verify
 * step — so these prove the outcome, not one guard.
 */

it('accepts the claim once when its link is used twice at the same moment, for an email with no login yet', function (): void {
    $claim = FamilyClaim::factory()->create(['email_normalised' => 'janice@example.com']);
    $token = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: 'janice@example.com', claim: $claim))->handle()->token;

    withCommittedWorld(function () use ($claim, $token): void {
        $signedIn = atTheSameTime(
            fn (): ?int => (new AcceptFamilyClaimOperation(token: $token))->handle()?->id,
            fn (): ?int => (new AcceptFamilyClaimOperation(token: $token))->handle()?->id,
        );

        $user = User::query()->where('email', 'janice@example.com')->sole();
        expect(array_values(array_filter($signedIn)))->toBe([$user->id])
            ->and(Guardian::query()->findOrFail($claim->guardian_id)->user_id)->toBe($user->id)
            ->and($claim->fresh()?->status)->toBe(FamilyClaimStatusEnum::Accepted);
    });
});

it('accepts the claim once when its link is used twice at the same moment, for an existing login', function (): void {
    $user = User::factory()->create(['email' => 'janice@example.com']);
    $claim = FamilyClaim::factory()->create(['email_normalised' => 'janice@example.com']);
    $token = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: 'janice@example.com', claim: $claim))->handle()->token;

    withCommittedWorld(function () use ($user, $claim, $token): void {
        $signedIn = atTheSameTime(
            fn (): ?int => (new AcceptFamilyClaimOperation(token: $token))->handle()?->id,
            fn (): ?int => (new AcceptFamilyClaimOperation(token: $token))->handle()?->id,
        );

        expect(array_values(array_filter($signedIn)))->toBe([$user->id])
            ->and(User::query()->where('email', 'janice@example.com')->count())->toBe(1)
            ->and(Guardian::query()->findOrFail($claim->guardian_id)->user_id)->toBe($user->id)
            ->and($claim->fresh()?->status)->toBe(FamilyClaimStatusEnum::Accepted);
    });
});
