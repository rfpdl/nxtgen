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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atClass;
use function Tests\Support\atSatellite;
use function Tests\Support\atTheSameTime;
use function Tests\Support\church;
use function Tests\Support\classAdminOf;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\staffMember;
use function Tests\Support\withCommittedWorld;

/*
 * People (D-R2): who serves at a satellite, and adding/removing their roles. Spec item 9 (DECISIONS 2026-11-08): there
 * is no church-wide Role — an admin of a satellite gives and removes any Role there (Admin or Lifeshaper) and the
 * Admin Role at its classes, nothing elsewhere; a satellite always keeps one admin. AccessScope::canGrant owns the
 * rule, these prove the screens ask it.
 */

/** @return array{church: Church, htc: Satellite, house: Satellite, admin: User} */
function peopleChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);
    $htc = satelliteOf($church, ['name' => 'Holy Trinity Church', 'slug' => 'htc']);
    $house = satelliteOf($church, ['name' => 'House Church', 'slug' => 'house']);
    $admin = staffMember(RoleEnum::Admin, atSatellite($htc), ['name' => 'Ana Admin']);

    return ['church' => $church, 'htc' => $htc, 'house' => $house, 'admin' => $admin];
}

/**
 * One admin removes a role through the whole request, from `$at`'s address; what they are told: "removed" or "refused". Refused
 * is also a 404: when the other admin's removal commits first, this one is no longer an admin at all.
 */
function removeAndSay(User $admin, RoleAssignment $role, string $at = 'htc'): string
{
    $response = actingAs($admin)->delete("/{$at}/admin/people/roles/{$role->id}");
    if ($response->status() === 404) {
        return 'refused';
    }
    $response->assertRedirect();

    return session()->has('success') ? 'removed' : 'refused';
}

/** @return array<string, mixed> */
function addPerson(string $email, RoleEnum $role, ScopeTypeEnum $type, int $scopeId, string $name = 'Leo Lifeshaper'): array
{
    return ['name' => $name, 'email' => $email, 'role' => $role->value, 'scope_type' => $type->value, 'scope_id' => $scopeId];
}

describe('the People page', function (): void {
    // Spec item 9: People lists only this satellite's Roles (and its classes'); no church-wide Role exists.
    it('shows an admin everyone who serves at their satellite and its classes, with their roles @story#74', function (): void {
        ['htc' => $htc, 'house' => $house, 'admin' => $admin] = peopleChurch();
        staffMember(RoleEnum::Lifeshaper, atSatellite($htc), ['name' => 'Leo Lifeshaper']);
        staffMember(RoleEnum::Admin, atClass(scheduleAt($htc, ['name' => '1PM'])), ['name' => 'Kai Class']);
        staffMember(RoleEnum::Lifeshaper, atSatellite($house), ['name' => 'Hana House']);
        staffMember(RoleEnum::Admin, atSatellite($house), ['name' => 'Hal House']);
        staffMember(RoleEnum::Admin, atClass(scheduleAt($house)), ['name' => 'Cleo House Class']);
        staffMember(RoleEnum::Admin, atSatellite(satelliteOf(church())), ['name' => 'Other Church Admin']);

        actingAs($admin)->get('/htc/admin/people')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/People/Index')
            ->where('people', fn (Collection $people): bool => $people->pluck('name')->all() === ['Ana Admin', 'Kai Class', 'Leo Lifeshaper'])
            ->where('people.0.roles.0.label', 'Admin · Holy Trinity Church')
            ->where('people.1.roles.0.label', 'Admin · Holy Trinity Church · 1PM')
            ->where('people.2.roles.0.label', 'Lifeshaper · Holy Trinity Church')
            ->where('people.2.roles.0.can_remove', true));
    });

    // Spec item 9 (was: a satellite admin gives only Lifeshaper) — an admin here gives Admin or Lifeshaper here, and
    // Admin at each of its active classes; nothing at another satellite.
    it('offers an admin only their satellite\'s Roles to give: Admin, Lifeshaper, and Admin at each active class @story#74', function (): void {
        ['htc' => $htc, 'house' => $house, 'admin' => $admin] = peopleChurch();
        scheduleAt($htc, ['name' => '1PM']);
        scheduleAt($htc, ['name' => 'Retired', 'is_active' => false]);
        scheduleAt($house, ['name' => '10AM']);

        actingAs($admin)->get('/htc/admin/people')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('grant_options', fn (Collection $options): bool => $options->pluck('label')->all() === [
                'Admin · Holy Trinity Church', 'Lifeshaper · Holy Trinity Church', 'Admin · Holy Trinity Church · 1PM',
            ]));
    });

    it('shows a person with Roles at two satellites on each satellite\'s People, with that satellite\'s Role — and they open both @story#74', function (): void {
        ['htc' => $htc, 'house' => $house, 'admin' => $admin] = peopleChurch();
        $houseAdmin = staffMember(RoleEnum::Admin, atSatellite($house), ['name' => 'Hal House']);
        $mia = staffMember(RoleEnum::Admin, atSatellite($htc), ['name' => 'Mia Both']);
        grantRole($mia, RoleEnum::Lifeshaper, atSatellite($house));

        /** @return array<mixed> */
        $mias = function (Collection $people): array {
            $mia = $people->firstWhere('name', 'Mia Both');

            return is_array($mia) && is_array($mia['roles'] ?? null) ? $mia['roles'] : [];
        };
        actingAs($admin)->get('/htc/admin/people')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('people', fn (Collection $people): bool => array_column($mias($people), 'label') === ['Admin · Holy Trinity Church']));
        actingAs($houseAdmin)->get('/house/admin/people')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('people', fn (Collection $people): bool => array_column($mias($people), 'label') === ['Lifeshaper · House Church']));

        actingAs($mia)->get('/htc/admin/people')->assertOk();
        actingAs($mia)->get('/house/serve/families')->assertOk();
    });

    it('puts People in the menu for admins only', function (): void {
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();
        $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($htc));

        actingAs($admin)->get('/htc/serve/families')->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_manage_people', true));
        actingAs($lifeshaper)->get('/htc/serve/families')->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_manage_people', false));
    });

    // DECISIONS 2026-11-08: a Lifeshaper who opens an Admin page is sent to their door; any Admin act is a 404.
    it('is not there for a Lifeshaper @story#75', function (): void {
        ['htc' => $htc] = peopleChurch();
        $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($htc));

        actingAs($lifeshaper)->get('/htc/admin/people')->assertRedirect('/htc/serve/door');
        actingAs($lifeshaper)->post('/htc/admin/people', addPerson('new@example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id))
            ->assertNotFound();

        expect(User::query()->where('email', 'new@example.com')->exists())->toBeFalse();
    });

    it('is not there for another satellite\'s admin @story#75', function (): void {
        ['house' => $house] = peopleChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get('/htc/admin/people')->assertNotFound();
    });
});

describe('adding someone', function (): void {
    it('creates the login, gives the role and emails a sign-in link @story#76', function (): void {
        Notification::fake();
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/htc/admin/people', addPerson('Leo@Example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id))
            ->assertRedirect()->assertSessionHas('success');

        $leo = User::query()->where('email', 'leo@example.com')->firstOrFail();
        expect($leo->name)->toBe('Leo Lifeshaper')
            ->and(RoleAssignment::query()->where('user_id', $leo->id)->where('is_active', true)->where('granted_by', $admin->id)->count())->toBe(1);

        Notification::assertSentOnDemand(MagicLinkNotification::class, fn (MagicLinkNotification $n, array $channels, AnonymousNotifiable $to): bool => $n->purpose === MagicLinkPurposeEnum::Login
            && $n->invitedAs === 'Lifeshaper · Holy Trinity Church'
            && $to->routes['mail'] === 'leo@example.com');
    });

    it('adds the role to an existing login (a parent who also serves) without a second account @story#76', function (): void {
        Notification::fake();
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();
        // A REAL parent (review 3: the fixture used to have no family, so it proved account reuse only).
        $guardianUser = User::factory()->create(['name' => 'Pia Parent', 'email' => 'pia@example.com']);
        $family = familyAt($htc);
        $guardian = guardianOf($family, $guardianUser);

        // The reply names the email the admin typed, never the account's own name (SECURITY #16).
        actingAs($admin)->post('/htc/admin/people', addPerson('pia@example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id, 'Typed Differently'))
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $reply): bool => str_contains($reply, 'pia@example.com') && ! str_contains($reply, 'Pia Parent'));

        expect(User::query()->where('email', 'pia@example.com')->count())->toBe(1)
            ->and($guardianUser->fresh()?->name)->toBe('Pia Parent')
            ->and(RoleAssignment::query()->where('user_id', $guardianUser->id)->count())->toBe(1);

        // One login, both places.
        actingAs($guardianUser)->get('/family')->assertOk();
        actingAs($guardianUser)->get('/htc/serve/families')->assertOk();
    });

    it('says so when the person already has that role', function (): void {
        Notification::fake();
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = staffMember(RoleEnum::Lifeshaper, atSatellite($htc), ['name' => 'Leo Lifeshaper']);

        actingAs($admin)->post('/htc/admin/people', addPerson($leo->email, RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id))
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $reply): bool => str_contains($reply, $leo->email) && ! str_contains($reply, $leo->name));
        expect(RoleAssignment::query()->where('user_id', $leo->id)->count())->toBe(1);
        Notification::assertNothingSent();
    });

    it('gives a removed role back instead of failing @story#76', function (): void {
        Notification::fake();
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = staffMember(RoleEnum::Lifeshaper, atSatellite($htc));
        RoleAssignment::query()->where('user_id', $leo->id)->update(['is_active' => false]);

        actingAs($admin)->post('/htc/admin/people', addPerson($leo->email, RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $htc->id))
            ->assertSessionHas('success');

        expect(RoleAssignment::query()->where('user_id', $leo->id)->where('is_active', true)->count())->toBe(1);
    });

    // Spec item 9 (was: only a church admin gives Admin) — an admin of a satellite gives the Admin Role there.
    it('lets a satellite admin give the Admin Role at their satellite @story#76', function (): void {
        Notification::fake();
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/htc/admin/people', addPerson('ben@example.com', RoleEnum::Admin, ScopeTypeEnum::Satellite, $htc->id, 'Ben Admin'))
            ->assertRedirect()->assertSessionHas('success');

        $ben = User::query()->where('email', 'ben@example.com')->firstOrFail();
        $role = RoleAssignment::query()->where('user_id', $ben->id)->sole();
        expect([$role->role, $role->scope_type, $role->scope_id, $role->is_active])->toBe([RoleEnum::Admin, ScopeTypeEnum::Satellite, $htc->id, true]);
        Notification::assertSentOnDemand(MagicLinkNotification::class, fn (MagicLinkNotification $n): bool => $n->invitedAs === 'Admin · Holy Trinity Church');
        actingAs($ben)->get('/htc/admin/people')->assertOk();
    });

    // Spec item 9 (was: a satellite admin held to Lifeshaper) — an admin gives nothing at another satellite.
    it('holds a satellite admin to their own satellite @story#77', function (RoleEnum $role): void {
        Notification::fake();
        ['house' => $house, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/htc/admin/people', addPerson('new@example.com', $role, ScopeTypeEnum::Satellite, $house->id))->assertNotFound();

        expect(User::query()->where('email', 'new@example.com')->exists())->toBeFalse();
    })->with([
        'Admin elsewhere' => [RoleEnum::Admin],
        'Lifeshaper elsewhere' => [RoleEnum::Lifeshaper],
    ]);

    it('gives no church-wide Role: there is none @story#77', function (): void {
        ['church' => $church, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/htc/admin/people', ['name' => 'New', 'email' => 'new@example.com', 'role' => 'admin', 'scope_type' => 'church', 'scope_id' => $church->id])
            ->assertSessionHasErrors('scope_type');

        expect(User::query()->where('email', 'new@example.com')->exists())->toBeFalse();
    });

    it('refuses a satellite of another church @story#77', function (): void {
        ['admin' => $admin] = peopleChurch();
        $foreign = Satellite::factory()->create();

        actingAs($admin)->post('/htc/admin/people', addPerson('new@example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $foreign->id))
            ->assertNotFound();
    });

    it('needs a name and a real email', function (): void {
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/htc/admin/people', ['name' => '', 'email' => 'nope', 'role' => 'lifeshaper', 'scope_type' => 'satellite', 'scope_id' => $htc->id])
            ->assertSessionHasErrors(['name', 'email']);
    });
});

describe('removing a role', function (): void {
    it('switches the role off and keeps it for the record @story#78', function (): void {
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = staffMember(RoleEnum::Lifeshaper, atSatellite($htc));
        $role = RoleAssignment::query()->where('user_id', $leo->id)->firstOrFail();

        actingAs($admin)->delete("/htc/admin/people/roles/{$role->id}")->assertRedirect()->assertSessionHas('success');

        expect($role->fresh()?->is_active)->toBeFalse();
        actingAs($leo)->get('/htc/serve/families')->assertNotFound();
    });

    // Spec item 9 (was: the last church admin) — a satellite always keeps one admin, counted per satellite.
    it('will not remove the last admin of a satellite, and says why @story#79', function (): void {
        ['house' => $house, 'admin' => $admin] = peopleChurch();
        staffMember(RoleEnum::Admin, atSatellite($house)); // another satellite's admin does not count here
        $role = RoleAssignment::query()->where('user_id', $admin->id)->sole();

        actingAs($admin)->delete("/htc/admin/people/roles/{$role->id}")
            ->assertRedirect()
            ->assertSessionHas('error', 'This is the only admin of Holy Trinity Church. Add another admin there first.');

        expect($role->fresh()?->is_active)->toBeTrue();
    });

    it('removes one of two admins of a satellite @story#78', function (): void {
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();
        $ben = staffMember(RoleEnum::Admin, atSatellite($htc), ['name' => 'Ben Admin']);
        $role = RoleAssignment::query()->where('user_id', $ben->id)->sole();

        actingAs($admin)->delete("/htc/admin/people/roles/{$role->id}")->assertRedirect()->assertSessionHas('success', 'Role removed.');

        expect($role->fresh()?->is_active)->toBeFalse();
        actingAs($ben)->get('/htc/admin/people')->assertNotFound();
    });

    // Spec item 9 (was: a satellite admin cannot remove a role above their own) — no Role is above a satellite admin's
    // now; what they cannot reach is a Role at another satellite, from either address.
    it('does not let an admin remove a Role at another satellite @story#79', function (): void {
        ['house' => $house, 'admin' => $admin] = peopleChurch();
        $hana = staffMember(RoleEnum::Lifeshaper, atSatellite($house));
        $role = RoleAssignment::query()->where('user_id', $hana->id)->sole();

        actingAs($admin)->delete("/htc/admin/people/roles/{$role->id}")->assertNotFound();
        actingAs($admin)->delete("/house/admin/people/roles/{$role->id}")->assertNotFound();

        expect($role->fresh()?->is_active)->toBeTrue();
    });

    it('cannot reach a role in another church @story#79', function (): void {
        ['admin' => $admin] = peopleChurch();
        $foreign = staffMember(RoleEnum::Lifeshaper, atSatellite(satelliteOf(church())));
        $role = RoleAssignment::query()->where('user_id', $foreign->id)->firstOrFail();

        actingAs($admin)->delete("/htc/admin/people/roles/{$role->id}")->assertNotFound();

        expect($role->fresh()?->is_active)->toBeTrue();
    });

    it('keeps one admin of a satellite when its last two remove each other at the same moment @story#79', function (): void {
        ['htc' => $htc, 'admin' => $ana] = peopleChurch();
        $ben = staffMember(RoleEnum::Admin, atSatellite($htc), ['name' => 'Ben Admin']);
        $anaRole = RoleAssignment::query()->where('user_id', $ana->id)->sole();
        $benRole = RoleAssignment::query()->where('user_id', $ben->id)->sole();

        withCommittedWorld(function () use ($ana, $ben, $anaRole, $benRole): void {
            // Each removes the other, each through the whole request (its transaction and locked count included).
            $answers = atTheSameTime(
                fn (): string => removeAndSay($ana, $benRole),
                fn (): string => removeAndSay($ben, $anaRole),
            );
            sort($answers);

            expect($answers)->toBe(['refused', 'removed'])
                ->and(RoleAssignment::query()->whereIn('id', [$anaRole->id, $benRole->id])->where('is_active', true)->count())->toBe(1);
        });
    });
});

/*
 * Class admins (TODO #9): the Admin Role at one class, given and removed by its satellite's admin (spec item 9).
 */
describe('class admins', function (): void {
    // Spec item 9 (was: a church admin at any class) — each satellite's admin makes class admins at its own classes.
    it('lets a satellite admin make someone class admin of a class at their satellite, at each satellite @story#180', function (): void {
        Notification::fake();
        ['htc' => $htc, 'house' => $house, 'admin' => $admin] = peopleChurch();
        $onePm = scheduleAt($htc, ['name' => '1PM']);
        $tenAm = scheduleAt($house, ['name' => '10AM']);
        $houseAdmin = staffMember(RoleEnum::Admin, atSatellite($house), ['name' => 'Hal House']);

        actingAs($houseAdmin)->post('/house/admin/people', addPerson('cleo@example.com', RoleEnum::Admin, ScopeTypeEnum::Schedule, $tenAm->id, 'Cleo Class'))
            ->assertSessionHas('success');
        actingAs($admin)->post('/htc/admin/people', addPerson('kai@example.com', RoleEnum::Admin, ScopeTypeEnum::Schedule, $onePm->id, 'Kai Class'))
            ->assertSessionHas('success');

        actingAs($admin)->get('/htc/admin/people')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('people', fn (Collection $people): bool => $people->pluck('name')->all() === ['Ana Admin', 'Kai Class'])
            ->where('people.1.roles.0.label', 'Admin · Holy Trinity Church · 1PM')
            ->where('people.1.roles.0.can_remove', true)
            ->where('grant_options', fn (Collection $options): bool => $options->pluck('label')->all() === ['Admin · Holy Trinity Church', 'Lifeshaper · Holy Trinity Church', 'Admin · Holy Trinity Church · 1PM']));
        expect(RoleAssignment::query()->where('scope_type', ScopeTypeEnum::Schedule)->pluck('scope_id')->sort()->values()->all())
            ->toBe(collect([$onePm->id, $tenAm->id])->sort()->values()->all());
    });

    it('refuses a class the admin does not look after, another church\'s class, a retired one, or Lifeshaper of a class @story#181', function (): void {
        Notification::fake();
        ['htc' => $htc, 'house' => $house, 'admin' => $admin] = peopleChurch();
        $onePm = scheduleAt($htc);
        $tenAm = scheduleAt($house);
        $retired = scheduleAt($htc, ['is_active' => false]);
        $theirs = scheduleAt(satelliteOf(church()));

        actingAs($admin)->post('/htc/admin/people', addPerson('a@example.com', RoleEnum::Admin, ScopeTypeEnum::Schedule, $tenAm->id))->assertNotFound();
        actingAs($admin)->post('/htc/admin/people', addPerson('b@example.com', RoleEnum::Admin, ScopeTypeEnum::Schedule, $theirs->id))->assertNotFound();
        actingAs($admin)->post('/htc/admin/people', addPerson('c@example.com', RoleEnum::Admin, ScopeTypeEnum::Schedule, $retired->id))->assertNotFound();
        actingAs($admin)->post('/htc/admin/people', addPerson('d@example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Schedule, $onePm->id))->assertNotFound();

        expect(RoleAssignment::query()->where('scope_type', ScopeTypeEnum::Schedule)->count())->toBe(0);
    });

    it('lets the satellite\'s admin remove a class admin of its class, and not another satellite\'s admin @story#180 @story#181', function (): void {
        ['htc' => $htc, 'house' => $house] = peopleChurch();
        $onePm = scheduleAt($htc);
        $classAdmin = classAdminOf($onePm);
        $role = RoleAssignment::query()->where('user_id', $classAdmin->id)->sole();

        expect(removeAndSay(staffMember(RoleEnum::Admin, atSatellite($house)), $role, 'house'))->toBe('refused')
            ->and(removeAndSay(staffMember(RoleEnum::Admin, atSatellite($htc)), $role))->toBe('removed');
    });
});

it('lets the platform admin open any satellite\'s Admin and Serve, and keeps them off its People @story#74', function (): void {
    $church = church(['slug' => 'ccf']);
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    staffMember(RoleEnum::Admin, atSatellite($dubai), ['name' => 'Dina Dubai']);
    $platform = User::factory()->create(['name' => 'Pat Platform', 'is_platform_admin' => true]);

    actingAs($platform)->get('/dubai/serve/door')->assertOk();
    actingAs($platform)->get('/dubai/admin/people')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->where('people', fn (Collection $people): bool => $people->pluck('name')->all() === ['Dina Dubai']));
    actingAs($platform)->get('/home')->assertRedirect('/dubai');
});
