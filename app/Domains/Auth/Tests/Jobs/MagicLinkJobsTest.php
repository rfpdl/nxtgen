<?php

declare(strict_types=1);

use App\Data\Models\FamilyClaim;
use App\Data\Models\MagicLink;
use App\Data\Models\User;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\RedeemMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Enums\FamilyClaimStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

function issueLogin(User $user): string
{
    return (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle()->token;
}

function redeem(string $token, MagicLinkPurposeEnum $purpose = MagicLinkPurposeEnum::Login): ?MagicLink
{
    return (new RedeemMagicLinkJob(token: $token, purpose: $purpose))->handle();
}

describe('CreateMagicLinkJob', function (): void {
    it('stores only a hash of the token', function (): void {
        $user = User::factory()->create();
        $token = issueLogin($user);

        $link = MagicLink::query()->sole();

        expect($link->token_hash)->toBe(hash('sha256', $token))
            ->and($link->token_hash)->not->toContain($token)
            ->and(strlen($token))->toBeGreaterThanOrEqual(43);
    });

    it('expires login links after 2 hours and verify links after 7 days', function (): void {
        Carbon::setTestNow('2026-10-15 10:00:00');
        $user = User::factory()->create();

        issueLogin($user);
        (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user))->handle();

        $links = MagicLink::query()->orderBy('id')->get();

        expect($links[0]?->expires_at->toDateTimeString())->toBe('2026-10-15 12:00:00')
            ->and($links[1]?->expires_at->toDateTimeString())->toBe('2026-10-22 10:00:00');
    });

    it('records the email and credential version it was issued for', function (): void {
        $user = User::factory()->create(['credential_version' => 3]);
        issueLogin($user);

        $link = MagicLink::query()->sole();

        expect($link->email_snapshot)->toBe($user->email)->and($link->credential_version)->toBe(3);
    });
});

describe('RedeemMagicLinkJob', function (): void {
    it('redeems a fresh link once', function (): void {
        $user = User::factory()->create();
        $token = issueLogin($user);

        expect(redeem($token)?->user_id)->toBe($user->id)
            ->and(redeem($token))->toBeNull();
    });

    it('refuses an unknown token', function (): void {
        expect(redeem('not-a-real-token'))->toBeNull();
    });

    it('refuses an expired link', function (): void {
        Carbon::setTestNow('2026-10-15 10:00:00');
        $token = issueLogin(User::factory()->create());

        Carbon::setTestNow('2026-10-15 12:00:01');

        expect(redeem($token))->toBeNull();
    });

    it('refuses a link redeemed for a different purpose', function (): void {
        $token = issueLogin(User::factory()->create());

        expect(redeem($token, MagicLinkPurposeEnum::VerifyEmail))->toBeNull();
    });

    it('refuses a login link after the account email changed', function (): void {
        $user = User::factory()->create();
        $token = issueLogin($user);

        $user->update(['email' => 'new@example.com']);

        expect(redeem($token))->toBeNull();
    });

    it('refuses a login link after the credential version was bumped', function (): void {
        $user = User::factory()->create();
        $token = issueLogin($user);

        $user->increment('credential_version');

        expect(redeem($token))->toBeNull();
    });

    it('refuses a claim link once the claim revision moved on', function (): void {
        $claim = FamilyClaim::factory()->create();
        $token = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: $claim->email_normalised, claim: $claim))->handle()->token;

        $claim->increment('revision');

        expect(redeem($token, MagicLinkPurposeEnum::ClaimFamily))->toBeNull();
    });

    it('refuses a claim link for a revoked claim', function (): void {
        $claim = FamilyClaim::factory()->create();
        $token = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: $claim->email_normalised, claim: $claim))->handle()->token;

        $claim->update(['status' => FamilyClaimStatusEnum::Revoked]);

        expect(redeem($token, MagicLinkPurposeEnum::ClaimFamily))->toBeNull();
    });

    it('redeems a valid claim link', function (): void {
        $claim = FamilyClaim::factory()->create();
        $token = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: $claim->email_normalised, claim: $claim))->handle()->token;

        expect(redeem($token, MagicLinkPurposeEnum::ClaimFamily)?->family_claim_id)->toBe($claim->id);
    });
});

describe('race: email corrected while a link is being redeemed (Codex diff review, high)', function (): void {
    it('consumes the link only if the email and credential version still match, in the same statement', function (): void {
        $user = User::factory()->unverified()->create(['email' => 'old@example.com']);
        $token = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user))->handle()->token;

        // The correction lands first; the old-address link must not be consumable afterwards.
        $user->forceFill(['email' => 'new@example.com', 'credential_version' => $user->credential_version + 1])->save();

        expect(redeem($token, MagicLinkPurposeEnum::VerifyEmail))->toBeNull()
            ->and(MagicLink::query()->sole()->used_at)->toBeNull();
    });

});

describe('SendMagicLinkJob recipient limit (Codex diff review, medium)', function (): void {
    it('sends at most 5 link emails to one address per 10 minutes, whichever page triggers them', function (): void {
        Notification::fake();
        $user = User::factory()->create();

        foreach (range(1, 7) as $_) {
            $issued = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle();
            (new SendMagicLinkJob(issued: $issued, churchName: 'CCF'))->handle();
        }

        Notification::assertSentOnDemandTimes(MagicLinkNotification::class, 5);
    });
});
