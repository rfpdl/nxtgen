<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Tests\Support\atChurch;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/*
 * Staff password backup (plan D7: "magic link + optional password for staff; magic link only for parents").
 * For the morning the email is slow at church: a staff member who set a password can still get in. It works only
 * while they serve somewhere; a parent never signs in with a password.
 */

function servingUser(?string $password = null): User
{
    $church = church();
    $user = User::factory()->create(['email' => 'leo@example.com', 'password' => $password]);
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));

    return $user;
}

describe('setting a backup password', function (): void {
    it('lets someone who serves set one @story#19 @story#21', function (): void {
        $user = servingUser();

        actingAs($user)->get('/account/password')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Auth/Password')->where('hasPassword', false));
        actingAs($user)->put('/account/password', ['password' => 'a-long-passphrase', 'password_confirmation' => 'a-long-passphrase'])
            ->assertRedirect()->assertSessionHas('success');

        expect(Hash::check('a-long-passphrase', (string) $user->fresh()?->password))->toBeTrue();
    });

    it('asks for at least 10 characters, typed twice the same @story#22', function (): void {
        $user = servingUser();

        actingAs($user)->put('/account/password', ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        actingAs($user)->put('/account/password', ['password' => 'a-long-passphrase', 'password_confirmation' => 'different-one!'])->assertSessionHasErrors('password');
    });

    it('is not found for a Guardian with no Role, to open or to set @story#20', function (): void {
        $church = church();
        $family = familyAt(satelliteOf($church));
        $guardian = User::factory()->create();
        guardianOf($family, $guardian);

        actingAs($guardian)->get('/account/password')->assertNotFound();
        actingAs($guardian)->put('/account/password', ['password' => 'a-long-passphrase', 'password_confirmation' => 'a-long-passphrase'])->assertNotFound();
    });
});

describe('signing in with it', function (): void {
    it('signs in someone who serves @story#16', function (): void {
        $user = servingUser('a-long-passphrase');

        post('/login/password', ['email' => 'Leo@Example.com', 'password' => 'a-long-passphrase'])->assertRedirect('/home');

        assertAuthenticatedAs($user);
        expect($user->fresh()?->last_login_at)->not->toBeNull();
    });

    it('gives the same answer for a wrong password and an unknown email @story#18', function (string $email, string $password): void {
        servingUser('a-long-passphrase');

        post('/login/password', ['email' => $email, 'password' => $password])
            ->assertSessionHasErrors(['email' => 'That email and password do not match. You can always use the email link instead.']);
        assertGuest();
    })->with([
        'wrong password' => ['leo@example.com', 'not-the-password'],
        'unknown email' => ['nobody@example.com', 'a-long-passphrase'],
    ]);

    it('stops working when they no longer serve anywhere @story#17', function (): void {
        $user = servingUser('a-long-passphrase');
        RoleAssignment::query()->where('user_id', $user->id)->update(['is_active' => false]);

        post('/login/password', ['email' => 'leo@example.com', 'password' => 'a-long-passphrase'])->assertSessionHasErrors('email');
        assertGuest();
    });

    it('never signs in a parent with a password, even one that has one @story#17', function (): void {
        $church = church();
        $family = familyAt(satelliteOf($church));
        $guardian = User::factory()->create(['email' => 'pia@example.com', 'password' => 'a-long-passphrase']);
        guardianOf($family, $guardian);

        post('/login/password', ['email' => 'pia@example.com', 'password' => 'a-long-passphrase'])->assertSessionHasErrors('email');
        assertGuest();
    });

    it('limits guessing @story#18', function (): void {
        servingUser('a-long-passphrase');

        foreach (range(1, 5) as $_) {
            post('/login/password', ['email' => 'leo@example.com', 'password' => 'wrong-guess-123']);
        }

        post('/login/password', ['email' => 'leo@example.com', 'password' => 'a-long-passphrase'])->assertStatus(429);
        assertGuest();
    });

    it('offers the password form on the sign-in page @story#8', function (): void {
        get('/login')->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/Login'));
    });
});
