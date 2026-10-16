<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\UpdateUserEmailVerifiedJob;

it('confirms the email when it is still the address the link was sent to', function (): void {
    $user = User::factory()->unverified()->create(['email' => 'janice@example.com']);

    expect((new UpdateUserEmailVerifiedJob(user: $user, boundEmail: 'janice@example.com', boundVersion: 1))->handle())->toBeTrue()
        ->and($user->fresh()?->email_verified_at)->not->toBeNull();
});

// Found by Codex diff review (high).
it('never marks a changed address verified from a link sent to the old one', function (): void {
    $user = User::factory()->unverified()->create(['email' => 'old@example.com']);

    $user->forceFill(['email' => 'new@example.com', 'credential_version' => 2])->save();

    expect((new UpdateUserEmailVerifiedJob(user: $user, boundEmail: 'old@example.com', boundVersion: 1))->handle())->toBeFalse()
        ->and($user->fresh()?->email_verified_at)->toBeNull();
});
