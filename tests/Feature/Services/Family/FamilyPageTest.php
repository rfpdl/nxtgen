<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\childEdit;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\familyEdit;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/** @return array{0: Church, 1: Family, 2: User} */
function familyWithGuardian(string $slug = 'ccf'): array
{
    $church = church(['slug' => $slug]);
    $satellite = satelliteOf($church, ['name' => 'Holy Trinity Church']);
    $family = familyAt($satellite, ['area_text' => 'Al Barsha']);
    $user = User::factory()->create();
    $guardian = guardianOf($family, $user, ['first_name' => 'Janice']);

    return [$church, $family, $user];
}

it('sends a guest to sign in', function (): void {
    familyWithGuardian();

    get('/ccf/family')->assertRedirect('/login');
});

it('shows a parent their own family, satellite and children with health details', function (): void {
    [, $family, $user] = familyWithGuardian();
    childOf($family, ['nickname' => 'Amara', 'allergies' => 'Peanuts']);

    actingAs($user)->get('/ccf/family')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Family/Show')
        ->where('family.home_satellite', 'Holy Trinity Church')
        ->where('family.area', 'Al Barsha')
        ->where('family.guardians.0.first_name', 'Janice')
        ->where('family.children.0.nickname', 'Amara')
        ->where('family.children.0.allergies', 'Peanuts'));
});

it('404s for someone signed in without a family in this church', function (): void {
    familyWithGuardian();

    actingAs(User::factory()->create())->get('/ccf/family')->assertNotFound();
});

it('does not show one church\'s family page to a parent of another church', function (): void {
    familyWithGuardian('ccf');
    [, , $otherGuardian] = familyWithGuardian('other');

    actingAs($otherGuardian)->get('/ccf/family')->assertNotFound();
});

it('lets a parent add a child, at the Level their age suggests', function (): void {
    [, $family, $user] = familyWithGuardian();

    actingAs($user)->post('/ccf/family/children', [
        'first_name' => 'Aaron', 'middle_name' => null, 'last_name' => 'Ramos', 'nickname' => 'Aaron',
        'gender' => 'boy', 'date_of_birth' => '2018-03-04', 'has_allergies' => false, 'allergies' => null,
    ])->assertRedirect('/ccf/family')->assertSessionHas('success', 'Aaron is added to your family.');

    // Eight on the church's reference date (2027-02-01) → Level 3.
    expect($family->children()->sole()->nickname)->toBe('Aaron')->and($family->children()->sole()->level)->toBe(3);
});

it('validates a new child in plain words', function (): void {
    [, , $user] = familyWithGuardian();

    actingAs($user)->post('/ccf/family/children', ['first_name' => ''])
        ->assertSessionHasErrors(['first_name' => 'Please enter the first name.', 'gender' => 'Please choose boy or girl.', 'date_of_birth' => 'Please enter the birthday.']);
});

it('keeps Laravel\'s own words for a too-long "Anything else we should know" (refactor 06 renamed the field)', function (): void {
    [, , $user] = familyWithGuardian();

    actingAs($user)->post('/ccf/family/children', [...childEdit(), 'guardian_notes' => str_repeat('a', 1001)])
        ->assertSessionHasErrors(['guardian_notes' => 'The parent notes field must not be greater than 1000 characters.']);
});

it('keeps Laravel\'s own words when "Anything else we should know" is not text', function (): void {
    [, , $user] = familyWithGuardian();

    actingAs($user)->post('/ccf/family/children', [...childEdit(), 'guardian_notes' => ['not', 'text']])
        ->assertSessionHasErrors(['guardian_notes' => 'The parent notes field must be a string.']);
});

it('lets a parent edit their own child, but not the status or Level', function (): void {
    [, $family, $user] = familyWithGuardian();
    $child = childOf($family, ['allergies' => null]);

    actingAs($user)->put("/ccf/family/children/{$child->id}", [
        'first_name' => $child->first_name, 'middle_name' => null, 'last_name' => $child->last_name, 'nickname' => 'Igo',
        'gender' => $child->gender->value, 'date_of_birth' => $child->date_of_birth->toDateString(), 'has_allergies' => true, 'allergies' => 'Shrimp',
        'status' => 'graduated', 'level' => 4,
    ])->assertRedirect('/ccf/family');

    $child->refresh();

    expect($child->nickname)->toBe('Igo')
        ->and($child->allergies)->toBe('Shrimp')
        ->and($child->status->value)->toBe('active')
        ->and($child->level)->toBeNull();
});

it('refuses to edit a child from another family', function (): void {
    [, , $user] = familyWithGuardian();
    $stranger = Child::factory()->create();

    actingAs($user)->put("/ccf/family/children/{$stranger->id}", [
        'first_name' => 'X', 'last_name' => 'Y', 'nickname' => 'X', 'gender' => 'boy', 'date_of_birth' => '2018-01-01', 'has_allergies' => false,
    ])->assertNotFound();

    expect($stranger->fresh()?->first_name)->not->toBe('X');
});

/*
 * The family page, editable (plan §3.2 "Edit family/guardian/child details": own family, not status/Level;
 * review B5). The page carries what the edit forms need; health details are the child's own family's to see.
 */

it('gives the page what the edit forms need, and every health detail', function (): void {
    [, $family, $user] = familyWithGuardian();
    childOf($family, ['nickname' => 'Amara',
        'allergies' => 'Peanuts', 'illnesses' => 'Asthma', 'dislikes' => 'Loud music', 'fears' => 'The dark', 'guardian_notes' => 'Shy at first',
    ]);

    actingAs($user)->get('/ccf/family')->assertInertia(fn (AssertableInertia $p) => $p
        ->where('details.home_satellite_id', $family->home_satellite_id)
        ->where('details.area_text', 'Al Barsha')
        ->where('me.first_name', 'Janice')
        ->has('satellites', 1)
        ->has('enums.relationship')
        ->where('family.children.0.illnesses', 'Asthma')
        ->where('family.children.0.dislikes', 'Loud music')
        ->where('family.children.0.fears', 'The dark')
        ->where('family.children.0.guardian_notes', 'Shy at first'));
});

it('lets a parent edit their family details and their own details', function (): void {
    [$church, $family, $user] = familyWithGuardian();
    $other = satelliteOf($church, ['name' => 'House Church']);

    actingAs($user)->put('/ccf/family', familyEdit($other))->assertRedirect('/ccf/family')->assertSessionHas('success');

    $family->refresh();
    $guardian = Guardian::query()->where('user_id', $user->id)->sole();
    expect($family->home_satellite_id)->toBe($other->id)
        ->and($family->area_text)->toBe('Deira')
        ->and($family->dgroup_leader_phone)->toBe('0501231234')
        ->and($family->lifestage)->toBe('Married with kids')
        ->and($guardian->last_name)->toBe('Ramos-Lee')
        ->and($guardian->phone)->toBe('0559998888')
        ->and($guardian->ministries)->toBe(['Music'])
        // The email is NOT editable here: it changes only through the confirm-by-link flow (plan §4.4).
        ->and($user->fresh()?->email)->toBe($user->email);
});

it('refuses a satellite from another church', function (): void {
    [, , $user] = familyWithGuardian();
    $foreign = Satellite::factory()->create();

    actingAs($user)->put('/ccf/family', familyEdit($foreign))->assertSessionHasErrors('home_satellite_id');
});

it('needs the parent\'s name and phone', function (): void {
    [$church, , $user] = familyWithGuardian();
    $satellite = Satellite::query()->where('church_id', $church->id)->firstOrFail();

    actingAs($user)->put('/ccf/family', familyEdit($satellite, ['guardian' => ['first_name' => '', 'phone' => '']]))
        ->assertSessionHasErrors(['guardian.first_name', 'guardian.phone']);
});

it('lets a parent fix a child\'s details but never the status or Level', function (): void {
    [, $family, $user] = familyWithGuardian();
    $child = childOf($family, ['status' => ChildStatusEnum::Active]);

    actingAs($user)->put("/ccf/family/children/{$child->id}", [
        'first_name' => 'Gabriel', 'last_name' => 'Ramos', 'nickname' => 'Gab', 'gender' => 'boy', 'date_of_birth' => '2017-05-10',
        'has_allergies' => true, 'allergies' => 'Eggs', 'fears' => 'Dogs', 'status' => 'graduated', 'level' => 4,
    ])->assertRedirect();

    $child->refresh();
    expect($child->nickname)->toBe('Gab')->and($child->allergies)->toBe('Eggs')->and($child->fears)->toBe('Dogs')
        ->and($child->status)->toBe(ChildStatusEnum::Active)->and($child->level)->toBeNull();
});

it('does not let a parent edit another family', function (): void {
    [$church, , $user] = familyWithGuardian();
    $satellite = Satellite::query()->where('church_id', $church->id)->firstOrFail();
    $otherFamily = familyAt($satellite, ['area_text' => 'Untouched']);

    // The family page edits only the signed-in parent's own family; there is no family id to point elsewhere.
    actingAs($user)->put('/ccf/family', familyEdit($satellite))->assertRedirect();

    expect($otherFamily->fresh()?->area_text)->toBe('Untouched');
});
