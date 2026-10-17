<?php

declare(strict_types=1);

use App\Data\Models\Guardian;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\UpdateFamilyGuardiansJob;
use App\Enums\NoteKindEnum;
use App\Enums\RelationshipEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\otherGuardianOf;
use function Tests\Support\satelliteOf;

/*
 * A family's other guardians (no login; never the primary) changed as an edit form says: a row with an id changes
 * that guardian, a row without one adds a guardian, and an id in the removed list removes that one. A guardian the
 * form does not mention is left alone — so an older page cannot remove one added since. Each change is noted on the
 * family's history with who made it. The answer says whether a phone came in or changed (the duplicate check).
 */

function edit(string $first, ?int $id = null, RelationshipEnum $relationship = RelationshipEnum::Grandparent, string $phone = '0501112222'): GuardianEditData
{
    return new GuardianEditData(first_name: $first, last_name: 'Ramos', relationship: $relationship, phone: $phone, ministries: [], id: $id);
}

/** @return list<string> */
function familyNotes(): array
{
    /** @var list<string> $bodies */
    $bodies = Note::query()->orderBy('id')->pluck('body')->all();

    return $bodies;
}

it('adds, changes and removes the guardians it is told to, noting each change with who made it', function (): void {
    $family = familyAt(satelliteOf(church()));
    $primary = guardianOf($family, User::factory()->create(), ['first_name' => 'Janice']);
    $lola = otherGuardianOf($family, ['first_name' => 'Lola', 'last_name' => 'Ramos', 'relationship' => RelationshipEnum::Grandparent, 'phone' => '0501112222']);
    $jun = otherGuardianOf($family, ['first_name' => 'Jun', 'last_name' => 'Ramos', 'relationship' => RelationshipEnum::Father]);
    $admin = User::factory()->create();

    $phones = (new UpdateFamilyGuardiansJob(family: $family, guardians: [edit('Lola', $lola->id, phone: '0503334444'), edit('Tita', relationship: RelationshipEnum::AuntUncle)], removedIds: [$jun->id], by: $admin))->handle();

    $others = Guardian::query()->where('family_id', $family->id)->whereKeyNot($primary->id)->orderBy('id')->get();
    expect($phones)->toBeTrue()
        ->and($others->map(fn (Guardian $g): array => [$g->first_name, $g->phone, $g->user_id])->all())
        ->toBe([['Lola', '0503334444', null], ['Tita', '0501112222', null]])
        ->and($primary->fresh()?->first_name)->toBe('Janice')
        ->and(familyNotes())->toBe([
            'Lola Ramos (Grandparent): details changed.',
            'Tita Ramos (Aunt / uncle) added to the family.',
            'Jun Ramos (Father) removed from the family.',
        ])
        ->and(Note::query()->pluck('author_id')->unique()->all())->toBe([$admin->id])
        ->and(Note::query()->pluck('kind')->unique()->all())->toBe([NoteKindEnum::System])
        ->and(Note::query()->pluck('noteable_type')->unique()->all())->toBe(['family']);
});

it('leaves alone a guardian the form does not mention — one added since the page was opened survives', function (): void {
    $family = familyAt(satelliteOf(church()));
    guardianOf($family, User::factory()->create());
    $lola = otherGuardianOf($family, ['first_name' => 'Lola', 'last_name' => 'Ramos', 'relationship' => RelationshipEnum::Grandparent, 'phone' => '0501112222', 'ministries' => []]);
    $addedSince = otherGuardianOf($family, ['first_name' => 'Jun']);

    (new UpdateFamilyGuardiansJob(family: $family, guardians: [edit('Lola', $lola->id)], removedIds: [], by: User::factory()->create()))->handle();

    expect($addedSince->fresh())->not->toBeNull();
});

it('notes nothing, and reports no phone change, for a guardian sent back unchanged or renamed', function (): void {
    $family = familyAt(satelliteOf(church()));
    guardianOf($family, User::factory()->create());
    $lola = otherGuardianOf($family, ['first_name' => 'Lola', 'last_name' => 'Ramos', 'relationship' => RelationshipEnum::Grandparent, 'phone' => '0501112222', 'ministries' => []]);

    $unchanged = (new UpdateFamilyGuardiansJob(family: $family, guardians: [edit('Lola', $lola->id)], removedIds: [], by: User::factory()->create()))->handle();
    $renamed = (new UpdateFamilyGuardiansJob(family: $family, guardians: [edit('Lolita', $lola->id)], removedIds: [], by: User::factory()->create()))->handle();

    expect([$unchanged, $renamed])->toBe([false, false])
        ->and(familyNotes())->toBe(['Lolita Ramos (Grandparent): details changed.']);
});

it('passes over a removal of a guardian already gone', function (): void {
    $family = familyAt(satelliteOf(church()));
    guardianOf($family, User::factory()->create());
    $gone = otherGuardianOf($family);
    $goneId = $gone->id;
    $gone->delete();

    (new UpdateFamilyGuardiansJob(family: $family, guardians: [], removedIds: [$goneId], by: User::factory()->create()))->handle();

    expect(familyNotes())->toBe([]);
});

it('never changes or removes the primary, a guardian with a login, or another family\'s guardian', function (string $who, bool $remove): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    // A primary still waiting on a claim has no login; the family's one login can then sit on another guardian.
    $primary = otherGuardianOf($family, ['first_name' => 'Janice']);
    $family->forceFill(['primary_guardian_id' => $primary->id])->save();
    $linked = Guardian::factory()->create(['family_id' => $family->id, 'first_name' => 'Linked', 'user_id' => User::factory()->create()->id]);
    $stranger = otherGuardianOf(familyAt($satellite), ['first_name' => 'Stranger']);
    $id = ['primary' => $primary, 'linked' => $linked, 'stranger' => $stranger][$who]->id;
    $before = Guardian::query()->orderBy('id')->get()->toArray();

    expect(fn () => (new UpdateFamilyGuardiansJob(
        family: $family,
        guardians: $remove ? [edit('Tita')] : [edit('Hacked', $id)],
        removedIds: $remove ? [$id] : [],
        by: User::factory()->create(),
    ))->handle())->toThrow(ModelNotFoundException::class);
    expect(Guardian::query()->orderBy('id')->get()->toArray())->toBe($before)
        ->and(familyNotes())->toBe([]);
})->with([
    'change the primary' => ['primary', false], 'remove the primary' => ['primary', true],
    'change one with a login' => ['linked', false], 'remove one with a login' => ['linked', true],
    'change another family\'s' => ['stranger', false], 'remove another family\'s' => ['stranger', true],
]);

it('refuses a family with no primary guardian, where every guardian would look removable', function (): void {
    $family = familyAt(satelliteOf(church()));
    $lola = otherGuardianOf($family, ['first_name' => 'Lola']);

    expect(fn () => (new UpdateFamilyGuardiansJob(family: $family, guardians: [], removedIds: [$lola->id], by: null))->handle())
        ->toThrow(ModelNotFoundException::class);
    expect($lola->fresh())->not->toBeNull();
});
