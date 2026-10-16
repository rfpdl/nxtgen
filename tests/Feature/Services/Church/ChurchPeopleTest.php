<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Notifications\MagicLinkNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * People (D-R2): who serves in the church, and adding/removing their roles. Plan §3.2 "Grant roles" row and
 * the role-grant ceiling; AccessScope::canGrant owns the rule, these prove the screens ask it.
 */

/** @return array{church: Church, htc: Satellite, house: Satellite, admin: User} */
function peopleChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);
    $htc = satelliteOf($church, ['name' => 'Holy Trinity Church']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    $admin = staffMember(RoleEnum::Admin, atChurch($church), ['name' => 'Ana Admin']);

    return ['church' => $church, 'htc' => $htc, 'house' => $house, 'admin' => $admin];
}

/** @return array<string, mixed> */
function addPerson(string $email, RoleEnum $role, ScopeTypeEnum $type, int $scopeId, string $name = 'Leo Lifeshaper'): array
{
    return ['name' => $name, 'email' => $email, 'role' => $role->value, 'scope_type' => $type->value, 'scope_id' => $scopeId];
}

describe('the People page', function (): void {
    it('shows a church admin everyone who serves, with their roles', function (): void {
        ['church' => $church, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        staffMember(RoleEnum::Lifeshaper, atSatellite($htc), ['name' => 'Leo Lifeshaper']);
        $other = church();
        staffMember(RoleEnum::Admin, atChurch($other), ['name' => 'Other Church Admin']);

        actingAs($admin)->get('/ccf/people')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/People/Index')
            ->has('people', 2)
            ->where('people.0.name', 'Ana Admin')
            ->where('people.0.roles.0.label', 'Admin · whole church')
            ->where('people.1.name', 'Leo Lifeshaper')
            ->where('people.1.roles.0.label', 'Lifeshaper · Holy Trinity Church')
            ->where('people.1.roles.0.canRemove', true)
            ->has('grantOptions', 6));
    });

    it('shows a satellite admin only their satellite, and only Lifeshaper to give', function (): void {
        ['church' => $church, 'htc' => $htc, 'house' => $house] = peopleChurch();
        $satAdmin = staffMember(RoleEnum::Admin, atSatellite($htc), ['name' => 'Sam Satellite']);
        staffMember(RoleEnum::Lifeshaper, atSatellite($htc), ['name' => 'Leo Lifeshaper']);
        staffMember(RoleEnum::Lifeshaper, atSatellite($house), ['name' => 'Hana House']);

        actingAs($satAdmin)->get('/ccf/people')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('people', 2)
            ->where('people.0.name', 'Leo Lifeshaper')
            ->where('people.1.name', 'Sam Satellite')
            ->where('people.1.roles.0.canRemove', false)
            ->has('grantOptions', 1)
            ->where('grantOptions.0.label', 'Lifeshaper · Holy Trinity Church'));
    });

    it('puts People in the menu for admins only', function (): void {
        ['church' => $church, 'admin' => $admin] = peopleChurch();
        $lifeshaper = staffMember(RoleEnum::Lifeshaper, atChurch($church));

        actingAs($admin)->get('/ccf/families')->assertInertia(fn (AssertableInertia $page) => $page->where('churchNav.canManagePeople', true));
        actingAs($lifeshaper)->get('/ccf/families')->assertInertia(fn (AssertableInertia $page) => $page->where('churchNav.canManagePeople', false));
    });

    it('is not there for a Lifeshaper', function (): void {
        ['church' => $church] = peopleChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atChurch($church)))
            ->get('/ccf/people')->assertNotFound();
    });
});

describe('adding someone', function (): void {
    it('creates the login, gives the role and emails a sign-in link', function (): void {
        Notification::fake();
        ['church' => $church, 'htc' => $htc, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/ccf/people', addPerson('Leo@Example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id))
            ->assertRedirect()->assertSessionHas('success');

        $leo = User::query()->where('email', 'leo@example.com')->firstOrFail();
        expect($leo->name)->toBe('Leo Lifeshaper')
            ->and(RoleAssignment::query()->where('user_id', $leo->id)->where('is_active', true)->where('granted_by', $admin->id)->count())->toBe(1);

        Notification::assertSentOnDemand(MagicLinkNotification::class, fn (MagicLinkNotification $n, array $channels, AnonymousNotifiable $to): bool => $n->purpose === MagicLinkPurposeEnum::Login
            && $n->invitedAs === 'Lifeshaper · Holy Trinity Church'
            && $to->routes['mail'] === 'leo@example.com');
    });

    it('adds the role to an existing login (a parent who also serves) without a second account', function (): void {
        Notification::fake();
        ['church' => $church, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        // A REAL parent (review 3: the fixture used to have no family, so it proved account reuse only).
        $guardianUser = User::factory()->create(['name' => 'Pia Parent', 'email' => 'pia@example.com']);
        $family = familyAt($htc);
        $guardian = guardianOf($family, $guardianUser);

        // The reply names the email the admin typed, never the account's own name (SECURITY #16).
        actingAs($admin)->post('/ccf/people', addPerson('pia@example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id, 'Typed Differently'))
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $reply): bool => str_contains($reply, 'pia@example.com') && ! str_contains($reply, 'Pia Parent'));

        expect(User::query()->where('email', 'pia@example.com')->count())->toBe(1)
            ->and($guardianUser->fresh()?->name)->toBe('Pia Parent')
            ->and(RoleAssignment::query()->where('user_id', $guardianUser->id)->count())->toBe(1);

        // One login, both places.
        actingAs($guardianUser)->get('/ccf/family')->assertOk();
        actingAs($guardianUser)->get('/ccf/families')->assertOk();
    });

    it('says so when the person already has that role', function (): void {
        Notification::fake();
        ['church' => $church, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = staffMember(RoleEnum::Lifeshaper, atSatellite($htc), ['name' => 'Leo Lifeshaper']);

        actingAs($admin)->post('/ccf/people', addPerson($leo->email, RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id))
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $reply): bool => str_contains($reply, $leo->email) && ! str_contains($reply, $leo->name));
        expect(RoleAssignment::query()->where('user_id', $leo->id)->count())->toBe(1);
        Notification::assertNothingSent();
    });

    it('gives a removed role back instead of failing', function (): void {
        Notification::fake();
        ['church' => $church, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = staffMember(RoleEnum::Lifeshaper, atSatellite($htc));
        RoleAssignment::query()->where('user_id', $leo->id)->update(['is_active' => false]);

        actingAs($admin)->post('/ccf/people', addPerson($leo->email, RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id))
            ->assertSessionHas('success');

        expect(RoleAssignment::query()->where('user_id', $leo->id)->where('is_active', true)->count())->toBe(1);
    });

    it('holds a satellite admin to Lifeshaper in their own satellite', function (RoleEnum $role, string $where): void {
        Notification::fake();
        ['church' => $church, 'htc' => $htc, 'house' => $house] = peopleChurch();
        $satAdmin = staffMember(RoleEnum::Admin, atSatellite($htc));
        [$type, $id] = match ($where) {
            'church' => [ScopeTypeEnum::Church, $church->id],
            'other satellite' => [ScopeTypeEnum::Satellite, $house->id],
            default => [ScopeTypeEnum::Satellite, $htc->id],
        };

        actingAs($satAdmin)->post('/ccf/people', addPerson('new@example.com', $role, $type, $id))->assertNotFound();

        expect(User::query()->where('email', 'new@example.com')->exists())->toBeFalse();
    })->with([
        'admin in own satellite' => [RoleEnum::Admin, 'own satellite'],
        'Lifeshaper church-wide' => [RoleEnum::Lifeshaper, 'church'],
        'Lifeshaper elsewhere' => [RoleEnum::Lifeshaper, 'other satellite'],
    ]);

    it('refuses a satellite of another church', function (): void {
        ['admin' => $admin] = peopleChurch();
        $foreign = Satellite::factory()->create();

        actingAs($admin)->post('/ccf/people', addPerson('new@example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $foreign->id))
            ->assertNotFound();
    });

    it('needs a name and a real email', function (): void {
        ['church' => $church, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/ccf/people', ['name' => '', 'email' => 'nope', 'role' => 'lifeshaper', 'scope_type' => 'church', 'scope_id' => $church->id])
            ->assertSessionHasErrors(['name', 'email']);
    });
});

describe('removing a role', function (): void {
    it('switches the role off and keeps it for the record', function (): void {
        ['church' => $church, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = staffMember(RoleEnum::Lifeshaper, atSatellite($htc));
        $role = RoleAssignment::query()->where('user_id', $leo->id)->firstOrFail();

        actingAs($admin)->delete("/ccf/people/roles/{$role->id}")->assertRedirect()->assertSessionHas('success');

        expect($role->fresh()?->is_active)->toBeFalse();
        actingAs($leo)->get('/ccf/families')->assertNotFound();
    });

    it('will not remove the last church admin', function (): void {
        ['admin' => $admin] = peopleChurch();
        $role = RoleAssignment::query()->where('user_id', $admin->id)->firstOrFail();

        actingAs($admin)->delete("/ccf/people/roles/{$role->id}")->assertSessionHas('error');

        expect($role->fresh()?->is_active)->toBeTrue();
    });

    it('does not let a satellite admin remove a role above their own', function (): void {
        ['church' => $church, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        $satAdmin = staffMember(RoleEnum::Admin, atSatellite($htc));
        $role = RoleAssignment::query()->where('user_id', $admin->id)->firstOrFail();

        actingAs($satAdmin)->delete("/ccf/people/roles/{$role->id}")->assertNotFound();

        expect($role->fresh()?->is_active)->toBeTrue();
    });

    it('cannot reach a role in another church', function (): void {
        ['admin' => $admin] = peopleChurch();
        $other = church();
        $foreign = staffMember(RoleEnum::Lifeshaper, atChurch($other));
        $role = RoleAssignment::query()->where('user_id', $foreign->id)->firstOrFail();

        actingAs($admin)->delete("/ccf/people/roles/{$role->id}")->assertNotFound();
    });
});
