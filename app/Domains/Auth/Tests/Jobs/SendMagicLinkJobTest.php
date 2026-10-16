<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Enums\MagicLinkPurposeEnum;
use App\Notifications\MagicLinkNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

/* Emails a link to the address it was issued for — at most 5 per address per 10 minutes, whichever page sends it. */

it('emails the link to its address, pointing at the confirm page for its purpose', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'janice@example.com']);
    $issued = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user))->handle();

    $sent = (new SendMagicLinkJob(issued: $issued, churchName: 'CCF', invitedAs: 'Lifeshaper'))->handle();

    expect($sent)->toBeTrue();
    Notification::assertSentOnDemand(
        MagicLinkNotification::class,
        fn (MagicLinkNotification $n, array $channels, AnonymousNotifiable $to): bool => $to->routes['mail'] === 'janice@example.com'
            && $n->url === route('auth.magic.confirm', ['purpose' => 'verify', 'token' => $issued->token])
            && $n->churchName === 'CCF'
            && $n->invitedAs === 'Lifeshaper',
    );
});

// Found by Codex diff review (medium).
it('sends at most 5 link emails to one address per 10 minutes, whichever page triggers them', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $results = [];
    foreach (range(1, 7) as $_) {
        $issued = (new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user))->handle();
        $results[] = (new SendMagicLinkJob(issued: $issued, churchName: 'CCF'))->handle();
    }

    Notification::assertSentOnDemandTimes(MagicLinkNotification::class, 5);
    expect($results)->toBe([true, true, true, true, true, false, false]);
});
