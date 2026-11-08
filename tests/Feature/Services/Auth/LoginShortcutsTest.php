<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Services\Auth\Features\Landing\ShowLandingFeature;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\withCookie;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;

/*
 * Two short addresses (Roy, 2026-11-06): /loginp opens the backup password straight away, and /loginc clears this
 * device's sign-in — signed out, the remembered landing page forgotten — and goes back to sign in.
 */

describe('/loginp', function (): void {
    it('opens the password sign-in on its own page @story#162', function (): void {
        get('/loginp')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/PasswordLogin'));
    });

    it('sends someone already signed in home, like a correct password would @story#162', function (): void {
        $user = User::factory()->create();
        grantRole($user, RoleEnum::Lifeshaper, atSatellite(satelliteOf(church())));

        actingAs($user)->get('/loginp')->assertRedirect('/home');
    });
});

describe('/loginc', function (): void {
    it('signs out, forgets the remembered landing page and goes back to sign in @story#163', function (): void {
        $user = User::factory()->create();
        grantRole($user, RoleEnum::Lifeshaper, atSatellite(satelliteOf(church())));

        $response = actingAs($user)->withCookie(ShowLandingFeature::REMEMBERED, '/ccf')->get('/loginc');

        $response->assertRedirect('/login')->assertSessionHas('success')->assertCookieExpired(ShowLandingFeature::REMEMBERED);
        assertGuest();
    });

    it('goes back to sign in for someone not signed in too @story#163', function (): void {
        withCookie(ShowLandingFeature::REMEMBERED, '/ccf')->get('/loginc')
            ->assertRedirect('/login')->assertCookieExpired(ShowLandingFeature::REMEMBERED);
        assertGuest();
    });
});
