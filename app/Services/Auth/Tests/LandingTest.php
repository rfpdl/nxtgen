<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function parentIn(Organization $org, User $user): void
{
    $family = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => Branch::factory()->for($org)->create()->id]);
    Guardian::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'user_id' => $user->id]);
}

function staffIn(Organization $org, User $user): void
{
    RoleAssignment::factory()->create([
        'organization_id' => $org->id, 'user_id' => $user->id,
        'role' => RoleEnum::Lifeshaper, 'scope_type' => ScopeTypeEnum::Organization, 'scope_id' => $org->id,
    ]);
}

it('sends a guest to sign in', function (): void {
    get('/home')->assertRedirect('/login');
});

it('sends a parent straight to their family page', function (): void {
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $user = User::factory()->create();
    parentIn($org, $user);

    actingAs($user)->get('/home')->assertRedirect('/ccf/family');
});

it('sends a volunteer straight to the dashboard', function (): void {
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $user = User::factory()->create();
    staffIn($org, $user);

    actingAs($user)->get('/home')->assertRedirect('/ccf');
});

it('lets a parent who also serves choose, with one login', function (): void {
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $user = User::factory()->create();
    parentIn($org, $user);
    staffIn($org, $user);

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Auth/Choose')
        ->has('options', 2)
        ->where('options.0.url', '/ccf/family')
        ->where('options.1.url', '/ccf'));
});

it('lets someone in two churches pick one', function (): void {
    $user = User::factory()->create();
    parentIn(Organization::factory()->create(['slug' => 'ccf']), $user);
    staffIn(Organization::factory()->create(['slug' => 'other']), $user);

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Auth/Choose')
        ->has('options', 2));
});

it('tells someone with no family or role that there is nothing here yet', function (): void {
    actingAs(User::factory()->create())->get('/home')->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/NothingYet'));
});

it('ignores a revoked role', function (): void {
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $user = User::factory()->create();
    staffIn($org, $user);
    RoleAssignment::query()->update(['is_active' => false]);

    actingAs($user)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Auth/NothingYet'));
});
