<?php

declare(strict_types=1);

use App\Data\Models\FamilyClaim;
use App\Data\Models\MagicLink;
use App\Data\Models\User;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\RedeemMagicLinkJob;
use App\Enums\FamilyClaimStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use Illuminate\Support\Carbon;

/* A link redeems exactly once, and only while what it was issued for (email + credential version, or the claim) still holds. */

function redeemTestLoginToken(User $user): string
{
    return (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle()->token;
}

function redeemTestClaimToken(FamilyClaim $claim): string
{
    return (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: $claim->email_normalised, claim: $claim))->handle()->token;
}

function redeemTestRedeem(string $token, MagicLinkPurposeEnum $purpose = MagicLinkPurposeEnum::Login): ?MagicLink
{
    return (new RedeemMagicLinkJob(token: $token, purpose: $purpose))->handle();
}

it('redeems a fresh link once', function (): void {
    $user = User::factory()->create();
    $token = redeemTestLoginToken($user);

    expect(redeemTestRedeem($token)?->user_id)->toBe($user->id)
        ->and(redeemTestRedeem($token))->toBeNull();
});

it('refuses an unknown token', function (): void {
    expect(redeemTestRedeem('not-a-real-token'))->toBeNull();
});

it('refuses an expired link', function (): void {
    Carbon::setTestNow('2026-10-15 10:00:00');
    $token = redeemTestLoginToken(User::factory()->create());

    Carbon::setTestNow('2026-10-15 12:00:01');

    expect(redeemTestRedeem($token))->toBeNull();
});

it('refuses a link redeemed for a different purpose', function (): void {
    $token = redeemTestLoginToken(User::factory()->create());

    expect(redeemTestRedeem($token, MagicLinkPurposeEnum::VerifyEmail))->toBeNull();
});

it('refuses a login link after the account email changed', function (): void {
    $user = User::factory()->create();
    $token = redeemTestLoginToken($user);

    $user->update(['email' => 'new@example.com']);

    expect(redeemTestRedeem($token))->toBeNull();
});

it('refuses a login link after the credential version was bumped', function (): void {
    $user = User::factory()->create();
    $token = redeemTestLoginToken($user);

    $user->increment('credential_version');

    expect(redeemTestRedeem($token))->toBeNull();
});

it('refuses a claim link once the claim revision moved on', function (): void {
    $claim = FamilyClaim::factory()->create();
    $token = redeemTestClaimToken($claim);

    $claim->increment('revision');

    expect(redeemTestRedeem($token, MagicLinkPurposeEnum::ClaimFamily))->toBeNull();
});

it('refuses a claim link for a revoked claim', function (): void {
    $claim = FamilyClaim::factory()->create();
    $token = redeemTestClaimToken($claim);

    $claim->update(['status' => FamilyClaimStatusEnum::Revoked]);

    expect(redeemTestRedeem($token, MagicLinkPurposeEnum::ClaimFamily))->toBeNull();
});

it('redeems a valid claim link', function (): void {
    $claim = FamilyClaim::factory()->create();
    $token = redeemTestClaimToken($claim);

    expect(redeemTestRedeem($token, MagicLinkPurposeEnum::ClaimFamily)?->family_claim_id)->toBe($claim->id);
});

// Found by Codex diff review (high).
it('consumes the link only if the email and credential version still match, in the same statement', function (): void {
    $user = User::factory()->unverified()->create(['email' => 'old@example.com']);
    $token = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user))->handle()->token;

    // The correction lands first; the old-address link must not be consumable afterwards.
    $user->forceFill(['email' => 'new@example.com', 'credential_version' => $user->credential_version + 1])->save();

    expect(redeemTestRedeem($token, MagicLinkPurposeEnum::VerifyEmail))->toBeNull()
        ->and(MagicLink::query()->sole()->used_at)->toBeNull();
});
