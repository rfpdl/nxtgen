<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\atChurch;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

it('sends a guest to sign in', function (): void {
    get('/home')->assertRedirect('/login');
});

it('sends a parent straight to their family page', function (): void {
    $church = church(['slug' => 'ccf']);
    $user = User::factory()->create();
    guardianOf(familyAt(satelliteOf($church)), $user);

    actingAs($user)->get('/home')->assertRedirect('/ccf/family');
});

it('sends a staff member straight to the dashboard', function (): void {
    $church = church(['slug' => 'ccf']);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));

    actingAs($user)->get('/home')->assertRedirect('/ccf');
});

it('lets a parent who also serves choose, with one login', function (): void {
    $church = church(['slug' => 'ccf']);
    $user = User::factory()->create();
    guardianOf(familyAt(satelliteOf($church)), $user);
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Auth/Choose')
        ->has('options', 2)
        ->where('options.0.url', '/ccf/family')
        ->where('options.1.url', '/ccf'));
});

it('lets someone in two churches pick one', function (): void {
    $user = User::factory()->create();
    $other = church(['slug' => 'other']);
    guardianOf(familyAt(satelliteOf(church(['slug' => 'ccf']))), $user);
    grantRole($user, RoleEnum::Lifeshaper, atChurch($other));

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Auth/Choose')
        ->has('options', 2));
});

it('tells someone with no family or role that there is nothing here yet', function (): void {
    actingAs(User::factory()->create())->get('/home')->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/NothingYet'));
});

it('ignores a revoked role', function (): void {
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
        guardianOf(familyAt(satelliteOf($church)), $user);
        grantRole($user, RoleEnum::Lifeshaper, atChurch($church));

        return [$user, $church];
    }

    it('goes where they chose, and remembers it', function (): void {
        [$user] = bothHats();

        $response = actingAs($user)->post('/home/choice', ['url' => '/ccf/family']);

        $response->assertRedirect('/ccf/family')->assertCookie('nxtgen_landing', '/ccf/family');
        actingAs($user)->withCookie('nxtgen_landing', '/ccf/family')->get('/home')->assertRedirect('/ccf/family');
    });

    it('asks again when they want to switch', function (): void {
        [$user] = bothHats();

        actingAs($user)->withCookie('nxtgen_landing', '/ccf/family')->get('/home?choose=1')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/Choose'));
    });

    it('forgets a choice that is no longer theirs', function (): void {
        [$user] = bothHats();
        RoleAssignment::query()->where('user_id', $user->id)->update(['is_active' => false]);

        // Only "My family" is left, so they go there whatever the cookie says.
        actingAs($user)->withCookie('nxtgen_landing', '/ccf')->get('/home')->assertRedirect('/ccf/family');
    });

    it('refuses a choice that is not one of their options — no sending people off-site', function (string $url): void {
        [$user] = bothHats();

        actingAs($user)->post('/home/choice', ['url' => $url])->assertNotFound();
        actingAs($user)->withCookie('nxtgen_landing', $url)->get('/home')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/Choose'));
    })->with(['https://evil.example/phish', '//evil.example', '/other/family']);

    it('tells the pages when there is more than one place to go, for the Switch link', function (): void {
        [$user] = bothHats();
        $guardianOnly = User::factory()->create();
        $ccf = Church::query()->where('slug', 'ccf')->firstOrFail();
        guardianOf(familyAt(satelliteOf($ccf)), $guardianOnly);

        actingAs($user)->get('/ccf/family')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.can_switch', true));
        actingAs($guardianOnly)->get('/ccf/family')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.can_switch', false));
    });
});
