<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/*
 * Where /home sends a login (plan §4.5). The church is never in an address (Roy 2026-11-07): a parent goes to
 * `/family`, staff to `/{satellite}` — the first satellite their Roles cover, in the order satellites were added.
 */

it('sends a guest to sign in', function (): void {
    get('/home')->assertRedirect('/login');
});

it('sends a parent straight to their family page @story#24', function (): void {
    $church = church(['slug' => 'ccf']);
    $user = User::factory()->create();
    guardianOf(familyAt(satelliteOf($church)), $user);

    actingAs($user)->get('/home')->assertRedirect('/family');
});

it('sends a staff member straight to the dashboard @story#24', function (): void {
    $church = church(['slug' => 'ccf']);
    $dubai = satelliteOf($church, ['name' => 'A first', 'slug' => 'dubai']);
    $sharjah = satelliteOf($church, ['name' => 'Added second', 'slug' => 'sharjah']);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($sharjah));
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($dubai));
    $satelliteStaff = User::factory()->create();
    grantRole($satelliteStaff, RoleEnum::Lifeshaper, atSatellite($sharjah));

    // Spec item 11: one satellite — its own; several — the one used last, else pick one.
    actingAs($satelliteStaff)->get('/home')->assertRedirect('/sharjah');
    actingAs($user)->get('/home')->assertRedirect('/pick');
    $user->forceFill(['last_satellite_id' => $sharjah->id])->save();
    actingAs($user)->get('/home')->assertRedirect('/sharjah');
});

it('lets a parent who also serves choose, with one login @story#24', function (): void {
    $church = church(['slug' => 'ccf']);
    $user = User::factory()->create();
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    guardianOf(familyAt($dubai), $user);
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($dubai));

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Auth/Choose')
        ->has('options', 2)
        ->where('options.0.url', '/family')
        ->where('options.1.url', '/dubai'));
});

it('lets someone in two churches pick one @story#24', function (): void {
    $user = User::factory()->create();
    $other = church(['slug' => 'other']);
    $otherSat = satelliteOf($other, ['slug' => 'other-sat']);
    guardianOf(familyAt(satelliteOf(church(['slug' => 'ccf']))), $user);
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($otherSat));

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Auth/Choose')
        ->has('options', 2)
        ->where('options', fn (Collection $options): bool => $options->pluck('url')->sort()->values()->all() === ['/family', '/other-sat']));
});

it('tells someone with no family or role that there is nothing here yet @story#25', function (): void {
    actingAs(User::factory()->create())->get('/home')->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/NothingYet'));
});

it('ignores a revoked role @story#25', function (): void {
    $church = church(['slug' => 'ccf']);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite(satelliteOf($church)));
    RoleAssignment::query()->update(['is_active' => false]);

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/NothingYet'));
});

/*
 * "Both → one-time choice, remembered, switchable from the menu" (plan §4.5). Remembered on this device, honoured
 * only while it is still one of the person's options, and never a way to send someone off-site.
 */
// Spec item 11: someone who serves at several satellites and used none yet picks one; the list is the satellites they
// may open, each going to its home there.
describe('Pick your satellite', function (): void {
    it('lists the satellites a person serves at, each opening its home @story#24 @story#186', function (): void {
        $church = church(['slug' => 'ccf']);
        $dubai = satelliteOf($church, ['slug' => 'dubai', 'name' => 'CCF Dubai']);
        $sharjah = satelliteOf($church, ['slug' => 'sharjah', 'name' => 'CCF Sharjah']);
        satelliteOf($church, ['slug' => 'abu-dhabi', 'name' => 'CCF Abu Dhabi']);
        $user = User::factory()->create();
        grantRole($user, RoleEnum::Admin, atSatellite($sharjah));
        grantRole($user, RoleEnum::Lifeshaper, atSatellite($dubai));

        actingAs($user)->get('/home')->assertRedirect('/pick');
        actingAs($user)->get('/pick')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Auth/PickSatellite')
            ->where('satellites', [['name' => 'CCF Dubai', 'slug' => 'dubai'], ['name' => 'CCF Sharjah', 'slug' => 'sharjah']]));
    });

    it('is for staff only: a parent gets not found, someone signed out signs in @story#25 @story#187', function (): void {
        $user = User::factory()->create();
        guardianOf(familyAt(satelliteOf(church())), $user);

        actingAs($user)->get('/pick')->assertNotFound();
        auth()->logout();
        get('/pick')->assertRedirect('/login');
    });
});

it('remembers the satellite last opened, on the account, and lands there next time @story#186', function (): void {
    $church = church(['slug' => 'ccf']);
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    $sharjah = satelliteOf($church, ['slug' => 'sharjah']);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($dubai));
    grantRole($user, RoleEnum::Admin, atSatellite($sharjah));

    actingAs($user)->get('/sharjah/admin/families')->assertOk();
    expect($user->fresh()?->last_satellite_id)->toBe($sharjah->id);
    actingAs($user)->get('/home')->assertRedirect('/sharjah');

    actingAs($user)->get('/dubai/serve/door')->assertOk();
    actingAs($user)->get('/home')->assertRedirect('/dubai');

    // Written only when it changes (review, issue 08): another Dubai page writes nothing.
    DB::enableQueryLog();
    actingAs($user)->get('/dubai/serve/door')->assertOk();
    DB::disableQueryLog();
    expect(collect(DB::getQueryLog())->pluck('query')->filter(fn (mixed $q): bool => is_string($q) && str_starts_with($q, 'update `users`'))->all())->toBe([]);
});

describe('remembering the choice', function (): void {
    /** @return array{0: User, 1: Church} */
    function bothHats(): array
    {
        $church = church(['slug' => 'ccf']);
        $user = User::factory()->create();
        $dubai = satelliteOf($church, ['slug' => 'dubai']);
        guardianOf(familyAt($dubai), $user);
        grantRole($user, RoleEnum::Lifeshaper, atSatellite($dubai));

        return [$user, $church];
    }

    it('goes where they chose, and remembers it @story#26', function (): void {
        [$user, $church] = bothHats();

        $response = actingAs($user)->post('/home/choice', ['place' => "serve:{$church->id}"]);

        $response->assertRedirect('/dubai')->assertCookie('nxtgen_landing', "serve:{$church->id}");
        actingAs($user)->withCookie('nxtgen_landing', "serve:{$church->id}")->get('/home')->assertRedirect('/dubai');
    });

    // Astra 2026-11-09: the choice is the PLACE (My family, Serve), not an address — Serve's address follows the
    // satellite used last (spec item 11), so a remembered "Serve" still holds when it changes.
    it('keeps "Serve" through a change of satellite — the address follows the one used last @story#26', function (): void {
        [$user, $church] = bothHats();
        $sharjah = satelliteOf($church, ['slug' => 'sharjah']);
        grantRole($user, RoleEnum::Lifeshaper, atSatellite($sharjah));

        actingAs($user)->post('/home/choice', ['place' => "serve:{$church->id}"])->assertRedirect('/pick');
        actingAs($user)->get('/sharjah/serve/door')->assertOk();

        actingAs($user)->withCookie('nxtgen_landing', "serve:{$church->id}")->get('/home')->assertRedirect('/sharjah');
        // A Choose page opened before the change still sends them on.
        actingAs($user)->post('/home/choice', ['place' => "serve:{$church->id}"])->assertRedirect('/sharjah');
    });

    it('asks again when they want to switch @story#26', function (): void {
        [$user] = bothHats();

        actingAs($user)->withCookie('nxtgen_landing', 'family')->get('/home?choose=1')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/Choose'));
    });

    it('forgets a choice that is no longer theirs @story#27', function (): void {
        [$user, $church] = bothHats();
        RoleAssignment::query()->where('user_id', $user->id)->update(['is_active' => false]);

        // Only "My family" is left, so they go there whatever the cookie says.
        actingAs($user)->withCookie('nxtgen_landing', "serve:{$church->id}")->get('/home')->assertRedirect('/family');
    });

    it('refuses a choice that is not one of their options — no sending people off-site @story#27', function (string $place): void {
        [$user] = bothHats();

        actingAs($user)->post('/home/choice', ['place' => $place])->assertNotFound();
        actingAs($user)->withCookie('nxtgen_landing', $place)->get('/home')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/Choose'));
    })->with(['https://evil.example/phish', '//evil.example', '/dubai', 'serve:999999', 'family:1']);

    it('tells the pages when there is more than one place to go, for the Switch link', function (): void {
        [$user] = bothHats();
        $guardianOnly = User::factory()->create();
        $ccf = Church::query()->where('slug', 'ccf')->firstOrFail();
        guardianOf(familyAt(satelliteOf($ccf)), $guardianOnly);

        actingAs($user)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.can_switch', true));
        actingAs($guardianOnly)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.can_switch', false));
    });
});
