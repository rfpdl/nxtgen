<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Note;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\LifestageEnum;
use App\Enums\RelationshipEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\checkedIn;
use function Tests\Support\childEdit;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\familyEdit;
use function Tests\Support\guardianOf;
use function Tests\Support\otherGuardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

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

    get('/family')->assertRedirect('/login');
});

it('shows a parent their own family, satellite and children with health details @story#28', function (): void {
    [, $family, $user] = familyWithGuardian();
    childOf($family, ['nickname' => 'Amara', 'allergies' => 'Peanuts']);

    actingAs($user)->get('/family')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Family/Show')
        ->where('family.home_satellite', 'Holy Trinity Church')
        ->where('family.area', 'Al Barsha')
        ->where('family.guardians.0.first_name', 'Janice')
        ->where('family.children.0.nickname', 'Amara')
        ->where('family.children.0.allergies', 'Peanuts'));
});

it('never shows a parent their child\'s attendance — that is for staff @story#127', function (): void {
    [, $family, $parent] = familyWithGuardian();
    $child = childOf($family, ['nickname' => 'Amara', 'level' => 2]);
    checkedIn(sessionOf(scheduleAt($family->homeSatellite)), $child, ['present_at' => now()]);

    actingAs($parent)->get('/family')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->missing('attendance')
        ->where('family.children', fn (Collection $children): bool => $children->every(fn (array $c): bool => ! array_key_exists('check_ins', $c) && ! array_key_exists('attendance', $c))));
});

it('never shows a parent a Card request for their child — staff file it and follow it @story#133', function (): void {
    [, $family, $parent] = familyWithGuardian();
    $child = childOf($family, ['nickname' => 'Amara']);
    CardRequest::factory()->for($child)->create();

    actingAs($parent)->get('/family')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->missing('card_requests')
        ->missing('enums.card_reason')
        // Nor any card field on a child (the parent's ChildPortalData has none) — whatever it might be called.
        ->where('family.children', fn (Collection $children): bool => $children->count() === 1
            && $children->every(fn (array $c): bool => preg_grep('/card/i', array_keys($c)) === [])));
});

it('404s for someone signed in without a family in this church @story#29', function (): void {
    familyWithGuardian();

    actingAs(User::factory()->create())->get('/family')->assertNotFound();
});

// DECISIONS 2026-11-08: the church is never in the address — `/family` is the signed-in parent's own family, so a
// parent of another church gets theirs, never this church's.
it('does not show one church\'s family page to a parent of another church @story#29', function (): void {
    familyWithGuardian('ccf');
    [, $otherFamily, $otherGuardian] = familyWithGuardian('other');
    $otherFamily->update(['area_text' => 'Mirdif']);

    actingAs($otherGuardian)->get('/family')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->where('church.slug', 'other')
        ->where('family.area', 'Mirdif'));
});

it('lets a parent add a child, at the Level their age suggests @story#32', function (): void {
    [, $family, $user] = familyWithGuardian();

    actingAs($user)->post('/family/children', [
        'first_name' => 'Aaron', 'middle_name' => null, 'last_name' => 'Ramos', 'nickname' => 'Aaron',
        'gender' => 'boy', 'date_of_birth' => '2018-03-04', 'has_allergies' => false, 'allergies' => null,
    ])->assertRedirect('/family')->assertSessionHas('success', 'Aaron is added to your family.');

    // Eight on the church's reference date (2027-02-01) → Level 3.
    expect($family->children()->sole()->nickname)->toBe('Aaron')->and($family->children()->sole()->level)->toBe(3);
});

it('validates a new child in plain words @story#33', function (): void {
    [, , $user] = familyWithGuardian();

    actingAs($user)->post('/family/children', ['first_name' => ''])
        ->assertSessionHasErrors(['first_name' => 'Please enter the first name.', 'gender' => 'Please choose boy or girl.', 'date_of_birth' => 'Please enter the birthday.']);
});

it('keeps Laravel\'s own words for a too-long "Anything else we should know" (refactor 06 renamed the field)', function (): void {
    [, , $user] = familyWithGuardian();

    actingAs($user)->post('/family/children', [...childEdit(), 'guardian_notes' => str_repeat('a', 1001)])
        ->assertSessionHasErrors(['guardian_notes' => 'The parent notes field must not be greater than 1000 characters.']);
});

it('keeps Laravel\'s own words when "Anything else we should know" is not text', function (): void {
    [, , $user] = familyWithGuardian();

    actingAs($user)->post('/family/children', [...childEdit(), 'guardian_notes' => ['not', 'text']])
        ->assertSessionHasErrors(['guardian_notes' => 'The parent notes field must be a string.']);
});

it('lets a parent edit their own child, but not the status or Level @story#34', function (): void {
    [, $family, $user] = familyWithGuardian();
    $child = childOf($family, ['allergies' => null]);

    actingAs($user)->put("/family/children/{$child->id}", [
        'first_name' => $child->first_name, 'middle_name' => null, 'last_name' => $child->last_name, 'nickname' => 'Igo',
        'gender' => $child->gender->value, 'date_of_birth' => $child->date_of_birth->toDateString(), 'has_allergies' => true, 'allergies' => 'Shrimp',
        'status' => 'graduated', 'level' => 4,
    ])->assertRedirect('/family');

    $child->refresh();

    expect($child->nickname)->toBe('Igo')
        ->and($child->allergies)->toBe('Shrimp')
        ->and($child->status->value)->toBe('active')
        ->and($child->level)->toBeNull();
});

it('refuses to edit a child from another family @story#35', function (): void {
    [, , $user] = familyWithGuardian();
    $stranger = Child::factory()->create();

    actingAs($user)->put("/family/children/{$stranger->id}", [
        'first_name' => 'X', 'last_name' => 'Y', 'nickname' => 'X', 'gender' => 'boy', 'date_of_birth' => '2018-01-01', 'has_allergies' => false,
    ])->assertNotFound();

    expect($stranger->fresh()?->first_name)->not->toBe('X');
});

// Astra 2026-11-08: a parent's child address finds only their OWN children, before any form is read — an empty form
// on another family's child (any church) answers as no child at all, so it confirms no child id.
it('answers another family\'s child as no child at all, whatever the form @story#35', function (): void {
    [, , $user] = familyWithGuardian();
    $theirs = childOf(familyAt(satelliteOf(church())));

    foreach ([$theirs->id, $theirs->id + 1000] as $id) {
        actingAs($user)->put("/family/children/{$id}", [])->assertNotFound();
        actingAs($user)->post("/family/children/{$id}/photo", [])->assertNotFound();
    }
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

    actingAs($user)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p
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

it('lets a parent edit their family details and their own details @story#30', function (): void {
    [$church, $family, $user] = familyWithGuardian();
    $other = satelliteOf($church, ['name' => 'House Church']);

    actingAs($user)->put('/family', familyEdit($other))->assertRedirect('/family')->assertSessionHas('success');

    $family->refresh();
    $guardian = Guardian::query()->where('user_id', $user->id)->sole();
    expect($family->home_satellite_id)->toBe($other->id)
        ->and($family->area_text)->toBe('Deira')
        ->and($family->dgroup_leader_phone)->toBe('0501231234')
        ->and($family->lifestage)->toBe(LifestageEnum::Married)
        ->and($guardian->last_name)->toBe('Ramos-Lee')
        ->and($guardian->phone)->toBe('0559998888')
        ->and($guardian->ministries)->toBe(['Music'])
        // The email is NOT editable here: it changes only through the confirm-by-link flow (plan §4.4).
        ->and($user->fresh()?->email)->toBe($user->email);
});

it('refuses a satellite from another church @story#31', function (): void {
    [, , $user] = familyWithGuardian();
    $foreign = Satellite::factory()->create();

    actingAs($user)->put('/family', familyEdit($foreign))->assertSessionHasErrors('home_satellite_id');
});

it('needs the parent\'s name and phone', function (): void {
    [$church, , $user] = familyWithGuardian();
    $satellite = Satellite::query()->where('church_id', $church->id)->firstOrFail();

    actingAs($user)->put('/family', familyEdit($satellite, ['guardian' => ['first_name' => '', 'phone' => '']]))
        ->assertSessionHasErrors(['guardian.first_name', 'guardian.phone']);
});

it('lets a parent fix a child\'s details but never the status or Level @story#34', function (): void {
    [, $family, $user] = familyWithGuardian();
    $child = childOf($family, ['status' => ChildStatusEnum::Active]);

    actingAs($user)->put("/family/children/{$child->id}", [
        'first_name' => 'Gabriel', 'last_name' => 'Ramos', 'nickname' => 'Gab', 'gender' => 'boy', 'date_of_birth' => '2017-05-10',
        'has_allergies' => true, 'allergies' => 'Eggs', 'fears' => 'Dogs', 'status' => 'graduated', 'level' => 4,
    ])->assertRedirect();

    $child->refresh();
    expect($child->nickname)->toBe('Gab')->and($child->allergies)->toBe('Eggs')->and($child->fears)->toBe('Dogs')
        ->and($child->status)->toBe(ChildStatusEnum::Active)->and($child->level)->toBeNull();
});

it('does not let a parent edit another family @story#31', function (): void {
    [$church, , $user] = familyWithGuardian();
    $satellite = Satellite::query()->where('church_id', $church->id)->firstOrFail();
    $otherFamily = familyAt($satellite, ['area_text' => 'Untouched']);

    // The family page edits only the signed-in parent's own family; there is no family id to point elsewhere.
    actingAs($user)->put('/family', familyEdit($satellite))->assertRedirect();

    expect($otherFamily->fresh()?->area_text)->toBe('Untouched');
});

/*
 * The family's other parents and guardians (DECISIONS 2026-10-17, after first look): the parent who signs in adds,
 * changes and removes them on "Edit family details". They have no login; the parent's own details are edited as before.
 */
it('lets a parent add, change and remove their family\'s other guardians, each change on the history @story#91', function (): void {
    [$church, $family, $user] = familyWithGuardian();
    $satellite = Satellite::query()->where('church_id', $church->id)->firstOrFail();
    $lolo = otherGuardianOf($family, ['first_name' => 'Lolo', 'last_name' => 'Ramos', 'relationship' => RelationshipEnum::Grandparent]);

    actingAs($user)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p
        ->has('other_guardians', 1)->where('other_guardians.0.id', $lolo->id)->where('me.first_name', 'Janice'));

    actingAs($user)->put('/family', familyEdit($satellite, [
        'guardians' => [['first_name' => 'Jun', 'last_name' => 'Ramos', 'relationship' => 'father', 'phone' => '0509990000']],
        'removed_guardian_ids' => [$lolo->id],
    ]))->assertRedirect('/family')->assertSessionHas('success');

    $others = Guardian::query()->where('family_id', $family->id)->whereKeyNot((int) $family->primary_guardian_id)->get();
    expect($others->map(fn (Guardian $g): array => [$g->first_name, $g->user_id])->all())->toBe([['Jun', null]])
        ->and(Note::query()->orderBy('id')->get(['body', 'author_id'])->toArray())->toBe([
            ['body' => 'Jun Ramos (Father) added to the family.', 'author_id' => $user->id],
            ['body' => 'Lolo Ramos (Grandparent) removed from the family.', 'author_id' => $user->id],
        ]);
});

it('never lets a parent change themselves or another family\'s guardian through the list @story#94', function (): void {
    [$church, $family, $user] = familyWithGuardian();
    $satellite = Satellite::query()->where('church_id', $church->id)->firstOrFail();
    $stranger = otherGuardianOf(familyAt($satellite), ['first_name' => 'Stranger']);
    $row = ['first_name' => 'Hacked', 'last_name' => 'X', 'relationship' => 'father', 'phone' => '0509990000'];

    actingAs($user)->put('/family', familyEdit($satellite, ['guardians' => [[...$row, 'id' => $stranger->id]]]))->assertNotFound();
    actingAs($user)->put('/family', familyEdit($satellite, ['guardians' => [[...$row, 'id' => $family->primary_guardian_id]]]))->assertNotFound();
    actingAs($user)->put('/family', familyEdit($satellite, ['removed_guardian_ids' => [$family->primary_guardian_id]]))->assertNotFound();
    actingAs($user)->put('/family', familyEdit($satellite, ['removed_guardian_ids' => [$stranger->id]]))->assertNotFound();

    expect(Guardian::query()->pluck('first_name')->sort()->values()->all())->toBe(['Janice', 'Stranger']);
});

it('refuses guardian changes on a family with no primary guardian, and saves nothing of the edit @story#94', function (): void {
    [$church, $family, $user] = familyWithGuardian();
    $satellite = Satellite::query()->where('church_id', $church->id)->firstOrFail();
    $lolo = otherGuardianOf($family, ['first_name' => 'Lolo']);
    $family->forceFill(['primary_guardian_id' => null])->save();

    actingAs($user)->put('/family', familyEdit($satellite, ['area_text' => 'Changed', 'removed_guardian_ids' => [$lolo->id]]))->assertNotFound();

    expect($lolo->fresh())->not->toBeNull()
        ->and($family->fresh()?->area_text)->toBe('Al Barsha');
});

// Found by Astra (2026-11-07): a lifestage stored before the three choices must not break the page.
it('reads a lifestage stored before Single, Married and KSP loosely: any capitals, anything else as none given @story#185', function (): void {
    [, $family, $user] = familyWithGuardian();

    DB::table('families')->where('id', $family->id)->update(['lifestage' => 'MARRIED']);
    actingAs($user)->get('/family')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->where('details.lifestage', 'married'));

    DB::table('families')->where('id', $family->id)->update(['lifestage' => 'Married with kids']);
    actingAs($user)->get('/family')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->where('details.lifestage', null));
});
