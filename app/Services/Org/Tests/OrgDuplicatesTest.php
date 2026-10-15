<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Note;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Enums\DuplicateStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/*
 * Possible duplicates (plan §4.2, §6 "dismiss/confirm duplicate candidates (confirm = flag for the later
 * merge)"): candidates are written at registration; staff who may edit the NEW family review them. Nothing is
 * ever merged here.
 */

/** @return array{org: Organization, htc: Branch, house: Branch, new: Family, old: Family} */
function dupChurch(): array
{
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $htc = Branch::factory()->for($org)->create(['name' => 'Holy Trinity Church']);
    $house = Branch::factory()->for($org)->create(['name' => 'House Church']);
    $old = dupFamily($org, $house, 'Maria', 'maria@old.com');
    $new = dupFamily($org, $htc, 'Mary', 'mary@new.com');
    (new CreateDuplicateCandidatesJob($new))->handle();

    return ['org' => $org, 'htc' => $htc, 'house' => $house, 'new' => $new, 'old' => $old];
}

function dupFamily(Organization $org, Branch $branch, string $name, string $email): Family
{
    $family = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $branch->id]);
    $user = User::factory()->create(['email' => $email]);
    $g = Guardian::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'user_id' => $user->id, 'first_name' => $name, 'last_name' => 'Santos', 'phone' => '+971 50 123 4567']);
    $family->forceFill(['primary_guardian_id' => $g->id])->save();
    Child::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'nickname' => "{$name}'s kid"]);

    return $family;
}

function dupStaff(Organization $org, RoleEnum $role, ScopeTypeEnum $type, int $id): User
{
    $u = User::factory()->create(['name' => 'Reviewer']);
    RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $u->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $id]);

    return $u;
}

it('lists open possible duplicates side by side, with masked emails', function (): void {
    ['org' => $org] = dupChurch();

    actingAs(dupStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->get('/ccf/duplicates')
        ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Org/Duplicates/Index')
        ->has('duplicates', 1)
        ->where('duplicates.0.reason', 'Same phone')
        ->where('duplicates.0.subject.primaryName', 'Mary Santos')
        ->where('duplicates.0.subject.satellite', 'Holy Trinity Church')
        ->where('duplicates.0.subject.maskedEmail', 'ma•••@new.com')
        ->where('duplicates.0.candidate.primaryName', 'Maria Santos')
        ->where('duplicates.0.candidate.children.0', "Maria's kid"));
});

it('shows a satellite admin only the duplicates of families they may edit', function (): void {
    ['org' => $org, 'htc' => $htc, 'house' => $house] = dupChurch();

    actingAs(dupStaff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id))->get('/ccf/duplicates')
        ->assertInertia(fn (AssertableInertia $p) => $p->has('duplicates', 1));
    actingAs(dupStaff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $house->id))->get('/ccf/duplicates')
        ->assertInertia(fn (AssertableInertia $p) => $p->has('duplicates', 0));
});

it('is not there for a Lifeshaper', function (): void {
    ['org' => $org] = dupChurch();

    actingAs(dupStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Organization, $org->id))->get('/ccf/duplicates')->assertNotFound();
});

it('dismisses a pair, records who, and notes it on the new family', function (): void {
    ['org' => $org, 'new' => $new] = dupChurch();
    $admin = dupStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);
    $pair = DuplicateCandidate::query()->sole();

    actingAs($admin)->put("/ccf/duplicates/{$pair->id}", ['status' => 'dismissed'])->assertRedirect()->assertSessionHas('success');

    $pair->refresh();
    expect($pair->status)->toBe(DuplicateStatusEnum::Dismissed)
        ->and($pair->resolved_by)->toBe($admin->id)
        ->and($pair->resolved_at)->not->toBeNull()
        ->and(Note::query()->where('noteable_type', 'family')->where('noteable_id', $new->id)->sole()->body)
        ->toBe('Possible duplicate of Maria Santos (same phone): not the same family.');
});

it('confirms a pair as the same family — flagged for the merge, nothing merged', function (): void {
    ['org' => $org] = dupChurch();
    $pair = DuplicateCandidate::query()->sole();

    actingAs(dupStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'confirmed'])->assertSessionHas('success');

    expect($pair->fresh()?->status)->toBe(DuplicateStatusEnum::Confirmed)
        ->and(Family::query()->count())->toBe(2);
});

it('refuses to review a pair outside the reviewer\'s satellite, or one already reviewed', function (): void {
    ['org' => $org, 'house' => $house] = dupChurch();
    $pair = DuplicateCandidate::query()->sole();

    actingAs(dupStaff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $house->id))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'dismissed'])->assertNotFound();

    $pair->forceFill(['status' => DuplicateStatusEnum::Dismissed])->save();
    actingAs(dupStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'confirmed'])->assertNotFound();
});

it('only takes "dismissed" or "confirmed"', function (): void {
    ['org' => $org] = dupChurch();
    $pair = DuplicateCandidate::query()->sole();

    actingAs(dupStaff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'open'])->assertSessionHasErrors('status');
});
