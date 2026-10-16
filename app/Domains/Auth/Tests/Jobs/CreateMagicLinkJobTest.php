<?php

declare(strict_types=1);

use App\Data\Models\FamilyClaim;
use App\Data\Models\MagicLink;
use App\Data\Models\User;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Enums\MagicLinkPurposeEnum;
use Illuminate\Support\Carbon;

/* A single-use link: only its hash is stored, bound to the email and credential version (or claim revision) it was issued for. */

it('stores only a hash of the token', function (): void {
    $user = User::factory()->create();
    $token = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle()->token;

    $link = MagicLink::query()->sole();

    expect($link->token_hash)->toBe(hash('sha256', $token))
        ->and($link->token_hash)->not->toContain($token)
        ->and(strlen($token))->toBeGreaterThanOrEqual(43);
});

it('expires login links after 2 hours and verify links after 7 days', function (): void {
    Carbon::setTestNow('2026-10-15 10:00:00');
    $user = User::factory()->create();

    (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle();
    (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user))->handle();

    $links = MagicLink::query()->orderBy('id')->get();

    expect($links[0]?->expires_at->toDateTimeString())->toBe('2026-10-15 12:00:00')
        ->and($links[1]?->expires_at->toDateTimeString())->toBe('2026-10-22 10:00:00');
});

it('records the email and credential version it was issued for', function (): void {
    $user = User::factory()->create(['credential_version' => 3]);
    (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle();

    $link = MagicLink::query()->sole();

    expect($link->email_snapshot)->toBe($user->email)->and($link->credential_version)->toBe(3);
});

it('binds a claim link to the claim and its revision, lower-casing the email', function (): void {
    $claim = FamilyClaim::factory()->create(['revision' => 4]);

    $issued = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: '  Janice@Example.COM ', claim: $claim))->handle();

    expect([$issued->link->family_claim_id, $issued->link->claim_revision, $issued->link->user_id, $issued->link->email_snapshot])
        ->toBe([$claim->id, 4, null, 'janice@example.com']);
});
