<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Notifications\MagicLinkNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/*
 * People (D-R2): who serves in the church, and adding/removing their roles. Plan §3.2 "Grant roles" row and
 * the role-grant ceiling; AccessScope::canGrant owns the rule, these prove the screens ask it.
 */

/** @return array{org: Organization, htc: Branch, house: Branch, admin: User} */
function peopleChurch(): array
{
    $org = Organization::factory()->create(['slug' => 'ccf', 'name' => 'CCF']);
    $htc = Branch::factory()->for($org)->create(['name' => 'Holy Trinity Church']);
    $house = Branch::factory()->for($org)->create(['name' => 'House Church']);
    $admin = peopleStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id, 'Ana Admin');

    return ['org' => $org, 'htc' => $htc, 'house' => $house, 'admin' => $admin];
}

function peopleStaff(Organization $org, RoleEnum $role, ScopeTypeEnum $type, int $scopeId, string $name = 'Staff'): User
{
    $user = User::factory()->create(['name' => $name]);
    RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $user->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $scopeId]);

    return $user;
}

/** @return array<string, mixed> */
function addPerson(string $email, RoleEnum $role, ScopeTypeEnum $type, int $scopeId, string $name = 'Leo Lifeshaper'): array
{
    return ['name' => $name, 'email' => $email, 'role' => $role->value, 'scope_type' => $type->value, 'scope_id' => $scopeId];
}

describe('the People page', function (): void {
    it('shows a church admin everyone who serves, with their roles', function (): void {
        ['org' => $org, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        peopleStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id, 'Leo Lifeshaper');
        $other = Organization::factory()->create();
        peopleStaff($other, RoleEnum::Admin, ScopeTypeEnum::Organization, $other->id, 'Other Church Admin');

        actingAs($admin)->get('/ccf/people')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Org/People/Index')
            ->has('people', 2)
            ->where('people.0.name', 'Ana Admin')
            ->where('people.0.roles.0.label', 'Admin · whole church')
            ->where('people.1.name', 'Leo Lifeshaper')
            ->where('people.1.roles.0.label', 'Lifeshaper · Holy Trinity Church')
            ->where('people.1.roles.0.canRemove', true)
            ->has('grantOptions', 6));
    });

    it('shows a satellite admin only their satellite, and only Lifeshaper to give', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house] = peopleChurch();
        $satAdmin = peopleStaff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id, 'Sam Satellite');
        peopleStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id, 'Leo Lifeshaper');
        peopleStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $house->id, 'Hana House');

        actingAs($satAdmin)->get('/ccf/people')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('people', 2)
            ->where('people.0.name', 'Leo Lifeshaper')
            ->where('people.1.name', 'Sam Satellite')
            ->where('people.1.roles.0.canRemove', false)
            ->has('grantOptions', 1)
            ->where('grantOptions.0.label', 'Lifeshaper · Holy Trinity Church'));
    });

    it('puts People in the menu for admins only', function (): void {
        ['org' => $org, 'admin' => $admin] = peopleChurch();
        $lifeshaper = peopleStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Organization, $org->id);

        actingAs($admin)->get('/ccf/families')->assertInertia(fn (AssertableInertia $page) => $page->where('orgNav.canManagePeople', true));
        actingAs($lifeshaper)->get('/ccf/families')->assertInertia(fn (AssertableInertia $page) => $page->where('orgNav.canManagePeople', false));
    });

    it('is not there for a Lifeshaper', function (): void {
        ['org' => $org] = peopleChurch();

        actingAs(peopleStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Organization, $org->id))
            ->get('/ccf/people')->assertNotFound();
    });
});

describe('adding someone', function (): void {
    it('creates the login, gives the role and emails a sign-in link', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/ccf/people', addPerson('Leo@Example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))
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
        ['htc' => $htc, 'admin' => $admin] = peopleChurch();
        $parent = User::factory()->create(['name' => 'Pia Parent', 'email' => 'pia@example.com']);

        actingAs($admin)->post('/ccf/people', addPerson('pia@example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id, 'Typed Differently'))
            ->assertRedirect()->assertSessionHas('success');

        expect(User::query()->where('email', 'pia@example.com')->count())->toBe(1)
            ->and($parent->fresh()?->name)->toBe('Pia Parent')
            ->and(RoleAssignment::query()->where('user_id', $parent->id)->count())->toBe(1);
    });

    it('says so when the person already has that role', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = peopleStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);

        actingAs($admin)->post('/ccf/people', addPerson($leo->email, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))
            ->assertRedirect()->assertSessionHas('error');

        expect(RoleAssignment::query()->where('user_id', $leo->id)->count())->toBe(1);
        Notification::assertNothingSent();
    });

    it('gives a removed role back instead of failing', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = peopleStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);
        RoleAssignment::query()->where('user_id', $leo->id)->update(['is_active' => false]);

        actingAs($admin)->post('/ccf/people', addPerson($leo->email, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))
            ->assertSessionHas('success');

        expect(RoleAssignment::query()->where('user_id', $leo->id)->where('is_active', true)->count())->toBe(1);
    });

    it('holds a satellite admin to Lifeshaper in their own satellite', function (RoleEnum $role, string $where): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc, 'house' => $house] = peopleChurch();
        $satAdmin = peopleStaff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id);
        [$type, $id] = match ($where) {
            'church' => [ScopeTypeEnum::Organization, $org->id],
            'other satellite' => [ScopeTypeEnum::Branch, $house->id],
            default => [ScopeTypeEnum::Branch, $htc->id],
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
        $foreign = Branch::factory()->create();

        actingAs($admin)->post('/ccf/people', addPerson('new@example.com', RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $foreign->id))
            ->assertNotFound();
    });

    it('needs a name and a real email', function (): void {
        ['org' => $org, 'admin' => $admin] = peopleChurch();

        actingAs($admin)->post('/ccf/people', ['name' => '', 'email' => 'nope', 'role' => 'lifeshaper', 'scope_type' => 'organization', 'scope_id' => $org->id])
            ->assertSessionHasErrors(['name', 'email']);
    });
});

describe('removing a role', function (): void {
    it('switches the role off and keeps it for the record', function (): void {
        ['org' => $org, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        $leo = peopleStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);
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
        ['org' => $org, 'htc' => $htc, 'admin' => $admin] = peopleChurch();
        $satAdmin = peopleStaff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id);
        $role = RoleAssignment::query()->where('user_id', $admin->id)->firstOrFail();

        actingAs($satAdmin)->delete("/ccf/people/roles/{$role->id}")->assertNotFound();

        expect($role->fresh()?->is_active)->toBeTrue();
    });

    it('cannot reach a role in another church', function (): void {
        ['admin' => $admin] = peopleChurch();
        $other = Organization::factory()->create();
        $foreign = peopleStaff($other, RoleEnum::Lifeshaper, ScopeTypeEnum::Organization, $other->id);
        $role = RoleAssignment::query()->where('user_id', $foreign->id)->firstOrFail();

        actingAs($admin)->delete("/ccf/people/roles/{$role->id}")->assertNotFound();
    });
});
