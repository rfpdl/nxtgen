<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\GetMagicLinkByTokenJob;
use App\Enums\MagicLinkPurposeEnum;

/* Reads a link by its token and purpose WITHOUT consuming it. */

it('finds the link for its token and purpose, and leaves it unused', function (): void {
    $user = User::factory()->create();
    $issued = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle();
    (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle();

    $found = (new GetMagicLinkByTokenJob(token: $issued->token, purpose: MagicLinkPurposeEnum::Login))->handle();

    expect($found?->id)->toBe($issued->link->id)
        ->and($found?->used_at)->toBeNull()
        ->and($issued->link->fresh()?->used_at)->toBeNull();
});

it('finds nothing for an unknown token or another purpose', function (): void {
    $user = User::factory()->create();
    $issued = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle();

    expect((new GetMagicLinkByTokenJob(token: 'not-a-real-token', purpose: MagicLinkPurposeEnum::Login))->handle())->toBeNull()
        ->and((new GetMagicLinkByTokenJob(token: $issued->token, purpose: MagicLinkPurposeEnum::ClaimFamily))->handle())->toBeNull();
});
