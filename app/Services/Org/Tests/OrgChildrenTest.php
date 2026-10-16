<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Group;
use App\Data\Models\Guardian;
use App\Data\Models\Note;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/*
 * The children list (plan §6 R1 "families/children lists") and setting a child's group (plan §3.2 "Set child
 * group (outside Moving-Up)": church admin only). Groups are assigned by age + gender at registration and
 * otherwise change only at Moving-Up — this is the admin's correction.
 */

/** @return array{org: Organization, dxb: Branch, house: Branch, girls2: Group, boys2: Group, amara: Child, bea: Child} */
function kidsChurch(): array
{
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $dxb = Branch::factory()->for($org)->create(['name' => 'CCF DXB']);
    $house = Branch::factory()->for($org)->create(['name' => 'House Church']);
    $girls2 = Group::factory()->create(['organization_id' => $org->id, 'name' => 'Level 2 Girls', 'gender' => GenderEnum::Girl, 'sort' => 21]);
    $boys2 = Group::factory()->create(['organization_id' => $org->id, 'name' => 'Level 2 Boys', 'gender' => GenderEnum::Boy, 'sort' => 20]);
    $a = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $dxb->id]);
    $b = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $house->id]);
    $amara = Child::factory()->create(['family_id' => $a->id, 'organization_id' => $org->id, 'first_name' => 'Alexis', 'nickname' => 'Amara', 'gender' => GenderEnum::Girl, 'allergies' => 'Peanuts', 'group_id' => $girls2->id, 'date_of_birth' => now()->subYears(7)->toDateString()]);
    $bea = Child::factory()->create(['family_id' => $b->id, 'organization_id' => $org->id, 'first_name' => 'Beatrice', 'nickname' => 'Bea', 'gender' => GenderEnum::Girl, 'allergies' => null, 'status' => ChildStatusEnum::Inactive]);

    return compact('org', 'dxb', 'house', 'girls2', 'boys2', 'amara', 'bea');
}

function kidsStaff(Organization $org, RoleEnum $role, ScopeTypeEnum $type, int $id, string $name = 'Staff'): User
{
    $u = User::factory()->create(['name' => $name]);
    RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $u->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $id]);

    return $u;
}

describe('the children list', function (): void {
    it('lists every child a church admin can see, with group, age, status and an allergy flag', function (): void {
        ['org' => $org, 'amara' => $amara] = kidsChurch();

        actingAs(kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->get('/ccf/children')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Org/Children/Index')
            ->has('children', 2)
            ->where('children.0.nickname', 'Amara')
            ->where('children.0.group', 'Level 2 Girls')
            ->where('children.0.age', 7)
            ->where('children.0.status', 'Active')
            ->where('children.0.hasAllergies', true)
            ->where('children.0.satellite', 'CCF DXB')
            ->where('children.0.familyId', $amara->family_id)
            ->has('options.groups', 2)->has('options.satellites', 2)->has('options.statuses', 3));
    });

    it('shows a Lifeshaper only their satellite', function (): void {
        ['org' => $org, 'house' => $house] = kidsChurch();

        actingAs(kidsStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $house->id))->get('/ccf/children')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea'));
    });

    it('searches and filters by group, status and satellite — never widening the viewer\'s scope', function (): void {
        ['org' => $org, 'dxb' => $dxb, 'house' => $house, 'girls2' => $girls2] = kidsChurch();
        $admin = kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);

        actingAs($admin)->get('/ccf/children?q=bea')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea')->where('filters.q', 'bea'));
        actingAs($admin)->get("/ccf/children?group={$girls2->id}")->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Amara'));
        actingAs($admin)->get('/ccf/children?status=inactive')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea'));
        actingAs($admin)->get("/ccf/children?satellite={$house->id}")->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1));

        actingAs(kidsStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $dxb->id))->get("/ccf/children?satellite={$house->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->has('children', 0));
    });

    it('finds children by their family\'s parent name, email or phone too — as the search box says (review 4)', function (): void {
        ['org' => $org, 'amara' => $amara] = kidsChurch();
        $user = User::factory()->create(['email' => 'janice@example.com']);
        Guardian::factory()->create(['family_id' => $amara->family_id, 'organization_id' => $org->id, 'user_id' => $user->id, 'first_name' => 'Janice', 'last_name' => 'Ramos', 'phone' => '0544897346']);
        $admin = kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);

        foreach (['Janice', 'janice@exam', '4489734'] as $q) {
            actingAs($admin)->get('/ccf/children?q='.urlencode($q))->assertInertia(fn (AssertableInertia $p) => $p
                ->has('children', 1)->where('children.0.nickname', 'Amara'));
        }
        // A child's own name finds that child, not their brothers and sisters.
        Child::factory()->create(['family_id' => $amara->family_id, 'organization_id' => $org->id, 'first_name' => 'Ben', 'nickname' => 'Ben', 'gender' => GenderEnum::Boy]);
        actingAs($admin)->get('/ccf/children?q=Amara')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Amara'));
    });

    it('is not there for someone with no role in the church', function (): void {
        kidsChurch();

        actingAs(User::factory()->create())->get('/ccf/children')->assertNotFound();
    });
});

describe('setting a child\'s group', function (): void {
    it('lets a church admin move a child, records who and when, and notes it', function (): void {
        ['org' => $org, 'boys2' => $boys2, 'bea' => $bea] = kidsChurch();
        $girls1 = Group::factory()->create(['organization_id' => $org->id, 'name' => 'Level 1', 'gender' => null]);
        $admin = kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id, 'Ana Admin');

        actingAs($admin)->put("/ccf/children/{$bea->id}/group", ['group_id' => $girls1->id])->assertRedirect()->assertSessionHas('success');

        $bea->refresh();
        expect($bea->group_id)->toBe($girls1->id)
            ->and($bea->group_set_by)->toBe($admin->id)
            ->and($bea->group_set_at)->not->toBeNull()
            ->and(Note::query()->where('noteable_type', 'child')->where('noteable_id', $bea->id)->sole()->body)
            ->toBe('Bea: group changed from no group to Level 1.');
        expect($boys2->id)->toBeInt();
    });

    it('refuses a group for the other gender', function (): void {
        ['org' => $org, 'boys2' => $boys2, 'amara' => $amara, 'girls2' => $girls2] = kidsChurch();

        actingAs(kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
            ->put("/ccf/children/{$amara->id}/group", ['group_id' => $boys2->id])
            ->assertSessionHasErrors(['group_id' => 'Level 2 Boys is for boys.']);

        expect($amara->fresh()?->group_id)->toBe($girls2->id);
    });

    it('refuses a retired group (review 4)', function (): void {
        ['org' => $org, 'amara' => $amara, 'girls2' => $girls2] = kidsChurch();
        $retired = Group::factory()->create(['organization_id' => $org->id, 'name' => 'Old Level', 'gender' => null, 'is_active' => false]);

        actingAs(kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
            ->put("/ccf/children/{$amara->id}/group", ['group_id' => $retired->id])->assertSessionHasErrors('group_id');

        expect($amara->fresh()?->group_id)->toBe($girls2->id)->and(Note::query()->count())->toBe(0);
    });

    it('refuses a group of another church', function (): void {
        ['org' => $org, 'amara' => $amara] = kidsChurch();
        $foreign = Group::factory()->create();

        actingAs(kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
            ->put("/ccf/children/{$amara->id}/group", ['group_id' => $foreign->id])->assertSessionHasErrors('group_id');
    });

    it('is for church admins only — not a satellite admin, not a Lifeshaper', function (RoleEnum $role): void {
        ['org' => $org, 'dxb' => $dxb, 'amara' => $amara, 'girls2' => $girls2, 'boys2' => $boys2] = kidsChurch();
        $girls1 = Group::factory()->create(['organization_id' => $org->id, 'name' => 'Level 1', 'gender' => null]);

        actingAs(kidsStaff($org, $role, ScopeTypeEnum::Branch, $dxb->id))
            ->put("/ccf/children/{$amara->id}/group", ['group_id' => $girls1->id])->assertNotFound();

        expect($amara->fresh()?->group_id)->toBe($girls2->id)->and($boys2->id)->toBeInt();
    })->with([RoleEnum::Admin, RoleEnum::Lifeshaper]);

    it('tells the family page who may change groups, and offers the church\'s groups', function (): void {
        ['org' => $org, 'dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->get("/ccf/families/{$amara->family_id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('canSetGroup', true)->has('groups', 2));
        actingAs(kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $dxb->id))->get("/ccf/families/{$amara->family_id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('canSetGroup', false)->where('groups', []));
    });
});

it('shows the children list 50 at a time, filters and all (Roy 2026-10-16)', function (): void {
    ['org' => $org, 'dxb' => $dxb] = kidsChurch();
    $family = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $dxb->id]);
    Child::factory()->count(58)->create(['family_id' => $family->id, 'organization_id' => $org->id, 'status' => ChildStatusEnum::Active]);
    $admin = kidsStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);

    actingAs($admin)->get('/ccf/children?status=active')->assertInertia(fn (AssertableInertia $p) => $p
        ->has('children', 50)->where('pagination.total', 59)->where('pagination.page', 1)->where('pagination.lastPage', 2));
    actingAs($admin)->get('/ccf/children?status=active&page=2')->assertInertia(fn (AssertableInertia $p) => $p
        ->has('children', 9)->where('pagination.page', 2));
});
