<?php

declare(strict_types=1);

use App\Data\Models\Family;
use App\Data\Models\FamilyClaim;
use App\Data\Models\Guardian;
use App\Data\Models\MagicLink;
use App\Data\Models\User;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Enums\FamilyClaimStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Notifications\MagicLinkNotification;
use App\Notifications\NoAccountNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function linkFor(MagicLinkPurposeEnum $purpose, string $email, ?User $user = null, ?FamilyClaim $claim = null): string
{
    return (new CreateMagicLinkJob(purpose: $purpose, email: $email, user: $user, claim: $claim))->handle()->token;
}

describe('requesting a sign-in link', function (): void {
    it('shows the sign-in page', function (): void {
        get('/login')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/Login'));
    });

    it('emails a sign-in link to a known email and says the same thing as for an unknown one', function (): void {
        Notification::fake();
        $user = User::factory()->create(['email' => 'janice@example.com']);

        post('/login/link', ['email' => ' Janice@Example.com '])
            ->assertRedirect('/login')
            ->assertSessionHas('success', 'If this email is registered, we have sent you a sign-in link.');

        Notification::assertSentOnDemand(MagicLinkNotification::class, fn (MagicLinkNotification $n, array $channels, object $notifiable): bool => $n->purpose === MagicLinkPurposeEnum::Login);
        expect($user->id)->toBeInt();
    });

    it('gives an unknown email the same answer, and emails that address a register-here note instead', function (): void {
        Notification::fake();

        post('/login/link', ['email' => 'stranger@example.com'])
            ->assertRedirect('/login')
            ->assertSessionHas('success', 'If this email is registered, we have sent you a sign-in link.');

        Notification::assertSentOnDemand(NoAccountNotification::class);
        Notification::assertSentOnDemandTimes(MagicLinkNotification::class, 0);
    });

    it('refuses an invalid email with a validation error', function (): void {
        post('/login/link', ['email' => 'not-an-email'])->assertSessionHasErrors('email');
    });

    it('throttles repeated requests for the same email', function (): void {
        Notification::fake();

        foreach (range(1, 5) as $_) {
            post('/login/link', ['email' => 'flood@example.com']);
        }

        post('/login/link', ['email' => 'flood@example.com'])->assertStatus(429);
    });
});

describe('opening a link', function (): void {
    it('only shows a Continue page when the link is opened, so an email scanner cannot use it up (Codex diff review, high)', function (): void {
        $user = User::factory()->create();
        $token = linkFor(MagicLinkPurposeEnum::Login, $user->email, $user);

        get('/magic/login/'.$token)->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Auth/ConfirmLink')
            ->where('purpose', 'login')
            ->where('action', url('/magic/login/'.$token)));

        assertGuest();
        expect(MagicLink::query()->sole()->used_at)->toBeNull();
    });

    it('does not accept a family claim just because the link was fetched', function (): void {
        $claim = FamilyClaim::factory()->create(['email_normalised' => 'janice@example.com']);

        get('/magic/claim/'.linkFor(MagicLinkPurposeEnum::ClaimFamily, 'janice@example.com', claim: $claim))->assertOk();

        expect($claim->fresh()?->status)->toBe(FamilyClaimStatusEnum::Pending);
    });

    it('signs in with a valid login link', function (): void {
        $user = User::factory()->create();

        post('/magic/login/'.linkFor(MagicLinkPurposeEnum::Login, $user->email, $user))->assertRedirect('/home');

        assertAuthenticatedAs($user);
    });

    it('refuses a used or bad link with one plain message', function (): void {
        post('/magic/login/nonsense')
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'That link has expired or was already used. Enter your email to get a new one.');

        assertGuest();
    });

    it('confirms the email and signs in with a verify link', function (): void {
        $user = User::factory()->unverified()->create();

        post('/magic/verify/'.linkFor(MagicLinkPurposeEnum::VerifyEmail, $user->email, $user))->assertRedirect('/home');

        expect($user->fresh()?->email_verified_at)->not->toBeNull();
        assertAuthenticatedAs($user);
    });

    it('accepts a family claim for an existing login and links the guardian', function (): void {
        $user = User::factory()->create(['email' => 'janice@example.com']);
        $claim = FamilyClaim::factory()->create(['email_normalised' => 'janice@example.com']);

        post('/magic/claim/'.linkFor(MagicLinkPurposeEnum::ClaimFamily, 'janice@example.com', claim: $claim))->assertRedirect('/home');

        expect($claim->fresh()?->status)->toBe(FamilyClaimStatusEnum::Accepted)
            ->and(Guardian::query()->findOrFail($claim->guardian_id)->user_id)->toBe($user->id);
        assertAuthenticatedAs($user);
    });

    it('creates the login when a claim is accepted for an email with no account yet', function (): void {
        $claim = FamilyClaim::factory()->create(['email_normalised' => 'new@example.com']);

        post('/magic/claim/'.linkFor(MagicLinkPurposeEnum::ClaimFamily, 'new@example.com', claim: $claim))->assertRedirect('/home');

        $user = User::query()->where('email', 'new@example.com')->sole();

        expect($user->email_verified_at)->not->toBeNull()
            ->and(Guardian::query()->findOrFail($claim->guardian_id)->user_id)->toBe($user->id);
    });

    it('revokes a claim when the login already belongs to another family in that church', function (): void {
        $user = User::factory()->create(['email' => 'janice@example.com']);
        $claim = FamilyClaim::factory()->create(['email_normalised' => 'janice@example.com']);
        Guardian::factory()->for(Family::factory()->state(['church_id' => $claim->church_id]))->create([
            'church_id' => $claim->church_id,
            'user_id' => $user->id,
        ]);

        post('/magic/claim/'.linkFor(MagicLinkPurposeEnum::ClaimFamily, 'janice@example.com', claim: $claim))
            ->assertRedirect('/login')
            ->assertSessionHas('error');

        expect($claim->fresh()?->status)->toBe(FamilyClaimStatusEnum::Revoked);
    });
});

it('signs out', function (): void {
    $user = User::factory()->create();

    \Pest\Laravel\actingAs($user)->post('/logout')->assertRedirect('/');

    assertGuest();
});

it('does not accept a claim when the account email changed after the claim was sent (Codex diff review, high)', function (): void {
    $user = User::factory()->unverified()->create(['email' => 'janice@example.com']);
    $claim = FamilyClaim::factory()->create(['email_normalised' => 'janice@example.com']);
    $token = linkFor(MagicLinkPurposeEnum::ClaimFamily, 'janice@example.com', claim: $claim);

    // Another church's admin corrected this login's email in the meantime.
    $user->forceFill(['email' => 'janice.new@example.com', 'credential_version' => 2])->save();

    post('/magic/claim/'.$token);

    // Whatever happens, the family is never attached to the account whose email moved away from this inbox.
    expect(Guardian::query()->findOrFail($claim->guardian_id)->user_id)->not->toBe($user->id)
        ->and(auth()->id())->not->toBe($user->id);
});
