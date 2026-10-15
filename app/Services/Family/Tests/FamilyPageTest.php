<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Organization;
use App\Data\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/** @return array{0: Organization, 1: Family, 2: User} */
function familyWithParent(string $slug = 'ccf'): array
{
    $org = Organization::factory()->create(['slug' => $slug]);
    $branch = Branch::factory()->for($org)->create(['name' => 'Holy Trinity Church']);
    $family = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $branch->id, 'area_text' => 'Al Barsha']);
    $user = User::factory()->create();
    $guardian = Guardian::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'user_id' => $user->id, 'first_name' => 'Janice']);
    $family->forceFill(['primary_guardian_id' => $guardian->id])->save();

    return [$org, $family, $user];
}

it('sends a guest to sign in', function (): void {
    familyWithParent();

    get('/ccf/family')->assertRedirect('/login');
});

it('shows a parent their own family, satellite and children with health details', function (): void {
    [, $family, $user] = familyWithParent();
    Child::factory()->create(['family_id' => $family->id, 'organization_id' => $family->organization_id, 'nickname' => 'Amara', 'allergies' => 'Peanuts']);

    actingAs($user)->get('/ccf/family')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Family/Show')
        ->where('family.homeSatellite', 'Holy Trinity Church')
        ->where('family.area', 'Al Barsha')
        ->where('family.guardians.0.firstName', 'Janice')
        ->where('family.children.0.nickname', 'Amara')
        ->where('family.children.0.allergies', 'Peanuts'));
});

it('404s for someone signed in without a family in this church', function (): void {
    familyWithParent();

    actingAs(User::factory()->create())->get('/ccf/family')->assertNotFound();
});

it('does not show one church\'s family page to a parent of another church', function (): void {
    familyWithParent('ccf');
    [, , $otherParent] = familyWithParent('other');

    actingAs($otherParent)->get('/ccf/family')->assertNotFound();
});

it('lets a parent add a child, with a suggested group', function (): void {
    [, $family, $user] = familyWithParent();

    actingAs($user)->post('/ccf/family/children', [
        'first_name' => 'Aaron', 'middle_name' => null, 'last_name' => 'Ramos', 'nickname' => 'Aaron',
        'gender' => 'boy', 'date_of_birth' => '2018-03-04', 'allergies' => null,
    ])->assertRedirect('/ccf/family')->assertSessionHas('success', 'Aaron is added to your family.');

    expect($family->children()->sole()->nickname)->toBe('Aaron');
});

it('validates a new child in plain words', function (): void {
    [, , $user] = familyWithParent();

    actingAs($user)->post('/ccf/family/children', ['first_name' => ''])
        ->assertSessionHasErrors(['first_name' => 'Please enter the first name.', 'gender' => 'Please choose boy or girl.', 'date_of_birth' => 'Please enter the birthday.']);
});

it('lets a parent edit their own child, but not the status or group', function (): void {
    [, $family, $user] = familyWithParent();
    $child = Child::factory()->create(['family_id' => $family->id, 'organization_id' => $family->organization_id, 'allergies' => null]);

    actingAs($user)->put("/ccf/family/children/{$child->id}", [
        'first_name' => $child->first_name, 'middle_name' => null, 'last_name' => $child->last_name, 'nickname' => 'Igo',
        'gender' => $child->gender->value, 'date_of_birth' => $child->date_of_birth->toDateString(), 'allergies' => 'Shrimp',
        'status' => 'graduated', 'group_id' => 999,
    ])->assertRedirect('/ccf/family');

    $child->refresh();

    expect($child->nickname)->toBe('Igo')
        ->and($child->allergies)->toBe('Shrimp')
        ->and($child->status->value)->toBe('active')
        ->and($child->group_id)->toBeNull();
});

it('refuses to edit a child from another family', function (): void {
    [, , $user] = familyWithParent();
    $stranger = Child::factory()->create();

    actingAs($user)->put("/ccf/family/children/{$stranger->id}", [
        'first_name' => 'X', 'last_name' => 'Y', 'gender' => 'boy', 'date_of_birth' => '2018-01-01',
    ])->assertNotFound();

    expect($stranger->fresh()?->first_name)->not->toBe('X');
});
