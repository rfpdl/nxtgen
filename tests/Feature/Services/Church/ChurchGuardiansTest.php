<?php

declare(strict_types=1);

use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Note;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\RelationshipEnum;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\familyEdit;
use function Tests\Support\guardianOf;
use function Tests\Support\otherGuardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * A family's other parents and guardians, on the family page (DECISIONS 2026-10-17, after first look): whoever may
 * edit the family adds, changes and removes them in "Edit family details". The form sends the rows it shows and the
 * ids it removed — a guardian it does not mention is left alone. The primary guardian — whose login is the family's —
 * is edited as before and never through this list.
 */

/** @return array{satellite: Satellite, family: Family, primary: Guardian, lola: Guardian} */
function guardianFamily(): array
{
    $satellite = satelliteOf(church(['slug' => 'ccf']), ['name' => 'Holy Trinity Church']);
    $family = familyAt($satellite);
    $primary = guardianOf($family, User::factory()->create(), ['first_name' => 'Janice', 'last_name' => 'Ramos']);
    $lola = otherGuardianOf($family, ['first_name' => 'Lola', 'last_name' => 'Ramos', 'relationship' => RelationshipEnum::Grandparent, 'phone' => '0501112222', 'ministries' => []]);

    return ['satellite' => $satellite, 'family' => $family, 'primary' => $primary, 'lola' => $lola];
}

/** @return array<string, mixed> one row of the form's other guardians */
function otherRow(string $first, ?int $id = null, string $relationship = 'grandparent', string $phone = '0501112222'): array
{
    return ['id' => $id, 'first_name' => $first, 'last_name' => 'Ramos', 'relationship' => $relationship, 'phone' => $phone, 'ministries' => []];
}

it('gives the family\'s editor its other guardians to edit — never the primary, who is edited as before @story#89', function (): void {
    ['satellite' => $satellite, 'family' => $family, 'lola' => $lola] = guardianFamily();

    actingAs(staffMember(RoleEnum::Admin, atSatellite($satellite)))->get("/ccf/families/{$family->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('primary.first_name', 'Janice')
            ->has('other_guardians', 1)
            ->where('other_guardians.0.id', $lola->id)
            ->where('other_guardians.0.first_name', 'Lola')
            ->where('other_guardians.0.relationship', 'grandparent'));
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($satellite)))->get("/ccf/families/{$family->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->has('other_guardians', 0)->has('family.guardians', 2));
});

it('lets an admin add, change and remove the other guardians, each change on the family\'s history @story#89', function (): void {
    ['satellite' => $satellite, 'family' => $family, 'primary' => $primary, 'lola' => $lola] = guardianFamily();
    $jun = otherGuardianOf($family, ['first_name' => 'Jun', 'last_name' => 'Ramos', 'relationship' => RelationshipEnum::Father]);
    $admin = staffMember(RoleEnum::Admin, atSatellite($satellite));

    actingAs($admin)->put("/ccf/families/{$family->id}", familyEdit($satellite, [
        'guardians' => [otherRow('Lola', $lola->id, phone: '0503334444'), otherRow('Tita', relationship: 'aunt_uncle')],
        'removed_guardian_ids' => [$jun->id],
    ]))->assertRedirect()->assertSessionHas('success', 'Family details saved.');

    expect(Guardian::query()->where('family_id', $family->id)->orderBy('id')->pluck('first_name')->all())->toBe(['Janice', 'Lola', 'Tita'])
        ->and($lola->fresh()?->phone)->toBe('0503334444')
        ->and(Guardian::query()->find($jun->id))->toBeNull()
        ->and($family->fresh()?->primary_guardian_id)->toBe($primary->id)
        ->and(Note::query()->where('noteable_id', $family->id)->orderBy('id')->get(['body', 'author_id'])->toArray())->toBe([
            ['body' => 'Lola Ramos (Grandparent): details changed.', 'author_id' => $admin->id],
            ['body' => 'Tita Ramos (Aunt / uncle) added to the family.', 'author_id' => $admin->id],
            ['body' => 'Jun Ramos (Father) removed from the family.', 'author_id' => $admin->id],
        ]);
});

it('keeps a guardian added since the form was opened: only the ones it removed go @story#89', function (): void {
    ['satellite' => $satellite, 'family' => $family, 'lola' => $lola] = guardianFamily();
    $admin = staffMember(RoleEnum::Admin, atSatellite($satellite));
    // Another admin adds Jun while this page still shows only Lola.
    $jun = otherGuardianOf($family, ['first_name' => 'Jun']);

    actingAs($admin)->put("/ccf/families/{$family->id}", familyEdit($satellite, ['guardians' => [otherRow('Lola', $lola->id)]]))->assertSessionHas('success');

    expect($jun->fresh())->not->toBeNull();
});

it('flags a possible duplicate when a guardian\'s phone comes in or changes — the newer family is the one answered for @story#89', function (): void {
    ['satellite' => $satellite, 'family' => $family, 'lola' => $lola] = guardianFamily();
    $newer = familyAt($satellite);
    guardianOf($newer, User::factory()->create(), ['phone' => '+971 55 777 8888']);

    actingAs(staffMember(RoleEnum::Admin, atChurch($satellite->church()->firstOrFail())))->put("/ccf/families/{$family->id}", familyEdit($satellite, [
        'guardians' => [otherRow('Lola', $lola->id), otherRow('Tito', relationship: 'aunt_uncle', phone: '0557778888')],
    ]))->assertSessionHas('success');

    expect(DuplicateCandidate::query()->sole()->only(['subject_id', 'candidate_id']))->toBe(['subject_id' => $newer->id, 'candidate_id' => $family->id]);
});

it('checks for possible duplicates again only when a phone came in or changed @story#89', function (): void {
    ['satellite' => $satellite, 'family' => $family, 'primary' => $primary, 'lola' => $lola] = guardianFamily();
    // A family sharing the primary's phone, never checked (made directly): an edit that touches no phone leaves it be.
    $match = familyAt($satellite);
    guardianOf($match, User::factory()->create(), ['phone' => '0559998888']);
    $primary->forceFill(['phone' => '0559998888'])->save();
    $admin = staffMember(RoleEnum::Admin, atSatellite($satellite));

    actingAs($admin)->put("/ccf/families/{$family->id}", familyEdit($satellite, [
        'area_text' => 'Karama', 'guardians' => [otherRow('Lolita', $lola->id)],
    ]))->assertSessionHas('success');
    expect(DuplicateCandidate::query()->count())->toBe(0);

    // The primary's own phone changed (to the same number, typed differently): checked again.
    actingAs($admin)->put("/ccf/families/{$family->id}", familyEdit($satellite, ['guardian' => ['phone' => '055 999 8888']]))->assertSessionHas('success');
    expect(DuplicateCandidate::query()->count())->toBe(1);
});

it('leaves the other guardians alone when the form sends none @story#89', function (): void {
    ['satellite' => $satellite, 'family' => $family] = guardianFamily();

    actingAs(staffMember(RoleEnum::Admin, atSatellite($satellite)))->put("/ccf/families/{$family->id}", familyEdit($satellite))->assertSessionHas('success');

    expect(Guardian::query()->where('family_id', $family->id)->count())->toBe(2)
        ->and(Note::query()->count())->toBe(0);
});

it('refuses to change or remove the primary guardian or another family\'s, and changes nothing @story#90', function (string $whose, bool $remove): void {
    ['satellite' => $satellite, 'family' => $family, 'primary' => $primary, 'lola' => $lola] = guardianFamily();
    $stranger = otherGuardianOf(familyAt($satellite), ['first_name' => 'Stranger']);
    $id = $whose === 'primary' ? $primary->id : $stranger->id;

    actingAs(staffMember(RoleEnum::Admin, atSatellite($satellite)))->put("/ccf/families/{$family->id}", familyEdit($satellite, [
        'area_text' => 'Changed',
        ...($remove ? ['removed_guardian_ids' => [$id]] : ['guardians' => [otherRow('Hacked', $id)]]),
    ]))->assertNotFound();

    expect(Guardian::query()->pluck('first_name')->sort()->values()->all())->toBe(['Janice', 'Lola', 'Stranger'])
        ->and($lola->fresh())->not->toBeNull()
        ->and($family->fresh()?->area_text)->not->toBe('Changed')
        ->and(Note::query()->count())->toBe(0);
})->with([
    'change the primary' => ['primary', false], 'remove the primary' => ['primary', true],
    'change another family\'s' => ['stranger', false], 'remove another family\'s' => ['stranger', true],
]);
