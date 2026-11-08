<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\atChurch;
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
    satelliteOf($church, ['name' => 'A first', 'slug' => 'dubai']);
    $sharjah = satelliteOf($church, ['name' => 'Added second', 'slug' => 'sharjah']);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));
    $satelliteStaff = User::factory()->create();
    grantRole($satelliteStaff, RoleEnum::Lifeshaper, atSatellite($sharjah));

    // A church-wide Role: the church's first-added satellite; a satellite Role: its own.
    actingAs($user)->get('/home')->assertRedirect('/dubai');
    actingAs($satelliteStaff)->get('/home')->assertRedirect('/sharjah');
});

it('lets a parent who also serves choose, with one login @story#24', function (): void {
    $church = church(['slug' => 'ccf']);
    $user = User::factory()->create();
    guardianOf(familyAt(satelliteOf($church, ['slug' => 'dubai'])), $user);
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Auth/Choose')
        ->has('options', 2)
        ->where('options.0.url', '/family')
        ->where('options.1.url', '/dubai'));
});

it('lets someone in two churches pick one @story#24', function (): void {
    $user = User::factory()->create();
    $other = church(['slug' => 'other']);
    satelliteOf($other, ['slug' => 'other-sat']);
    guardianOf(familyAt(satelliteOf(church(['slug' => 'ccf']))), $user);
    grantRole($user, RoleEnum::Lifeshaper, atChurch($other));

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
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));
    RoleAssignment::query()->update(['is_active' => false]);

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/NothingYet'));
});

/*
 * "Both → one-time choice, remembered, switchable from the menu" (plan §4.5). Remembered on this device, honoured
 * only while it is still one of the person's options, and never a way to send someone off-site.
 */
describe('remembering the choice', function (): void {
    /** @return array{0: User, 1: Church} */
    function bothHats(): array
    {
        $church = church(['slug' => 'ccf']);
        $user = User::factory()->create();
        guardianOf(familyAt(satelliteOf($church, ['slug' => 'dubai'])), $user);
        grantRole($user, RoleEnum::Lifeshaper, atChurch($church));

        return [$user, $church];
    }

    it('goes where they chose, and remembers it @story#26', function (): void {
        [$user] = bothHats();

        $response = actingAs($user)->post('/home/choice', ['url' => '/dubai']);

        $response->assertRedirect('/dubai')->assertCookie('nxtgen_landing', '/dubai');
        actingAs($user)->withCookie('nxtgen_landing', '/dubai')->get('/home')->assertRedirect('/dubai');
    });

    it('asks again when they want to switch @story#26', function (): void {
        [$user] = bothHats();

        actingAs($user)->withCookie('nxtgen_landing', '/family')->get('/home?choose=1')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/Choose'));
    });

    it('forgets a choice that is no longer theirs @story#27', function (): void {
        [$user] = bothHats();
        RoleAssignment::query()->where('user_id', $user->id)->update(['is_active' => false]);

        // Only "My family" is left, so they go there whatever the cookie says.
        actingAs($user)->withCookie('nxtgen_landing', '/dubai')->get('/home')->assertRedirect('/family');
    });

    it('refuses a choice that is not one of their options — no sending people off-site @story#27', function (string $url): void {
        [$user] = bothHats();

        actingAs($user)->post('/home/choice', ['url' => $url])->assertNotFound();
        actingAs($user)->withCookie('nxtgen_landing', $url)->get('/home')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/Choose'));
    })->with(['https://evil.example/phish', '//evil.example', '/sharjah', '/ccf/family']);

    it('tells the pages when there is more than one place to go, for the Switch link', function (): void {
        [$user] = bothHats();
        $guardianOnly = User::factory()->create();
        $ccf = Church::query()->where('slug', 'ccf')->firstOrFail();
        guardianOf(familyAt(satelliteOf($ccf)), $guardianOnly);

        actingAs($user)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.can_switch', true));
        actingAs($guardianOnly)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.can_switch', false));
    });
});
