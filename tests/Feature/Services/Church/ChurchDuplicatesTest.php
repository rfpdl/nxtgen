<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Enums\DuplicateStatusEnum;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * Possible duplicates (plan §4.2, §6 "dismiss/confirm duplicate candidates (confirm = flag for the later
 * merge)"): candidates are written at registration; staff who may edit the NEW family review them — on either
 * family's page, since the Duplicates tab went (DECISIONS 2026-10-17, after first look). Nothing is ever merged here.
 */

/** @return array{church: Church, htc: Satellite, house: Satellite, new: Family, old: Family} */
function dupChurch(): array
{
    $church = church(['slug' => 'ccf']);
    $htc = satelliteOf($church, ['name' => 'Holy Trinity Church']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    $old = dupFamily($house, 'Maria', 'maria@old.com');
    $new = dupFamily($htc, 'Mary', 'mary@new.com');
    (new CreateDuplicateCandidatesJob($new))->handle();

    return ['church' => $church, 'htc' => $htc, 'house' => $house, 'new' => $new, 'old' => $old];
}

function dupFamily(Satellite $satellite, string $name, string $email): Family
{
    $family = familyAt($satellite);
    $user = User::factory()->create(['email' => $email]);
    $g = guardianOf($family, $user, ['first_name' => $name, 'last_name' => 'Santos', 'phone' => '+971 50 123 4567']);
    childOf($family, ['nickname' => "{$name}'s kid"]);

    return $family;
}

it('shows a family\'s open possible duplicates on its page, both sides in the lite view, to an admin who may answer @story#92', function (): void {
    ['church' => $church, 'new' => $new, 'old' => $old] = dupChurch();
    $admin = staffMember(RoleEnum::Admin, atChurch($church));

    // On either side's page: the new family's, and the existing one it may duplicate.
    foreach ([$new, $old] as $family) {
        actingAs($admin)->get("/ccf/families/{$family->id}")
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Church/Families/Show')
            ->has('duplicates', 1)
            ->where('duplicates.0.reason_label', 'Same phone')
            ->where('duplicates.0.subject.primary_name', 'Mary Santos')
            ->where('duplicates.0.subject.masked_email', 'ma•••@new.com')
            ->where('duplicates.0.candidate.primary_name', 'Maria Santos')
            ->where('duplicates.0.candidate.children.0.nickname', "Maria's kid"));
    }
});

it('shows a pair of children on both children\'s families\' pages @story#92', function (): void {
    $church = church(['slug' => 'ccf']);
    $htc = satelliteOf($church);
    $old = familyAt($htc);
    $new = familyAt($htc);
    guardianOf($old, User::factory()->create(), ['phone' => '0501111111']);
    guardianOf($new, User::factory()->create(), ['phone' => '0502222222']);
    childOf($old, ['first_name' => 'Ana', 'last_name' => 'Cruz', 'date_of_birth' => '2018-01-01']);
    childOf($new, ['first_name' => 'Ana', 'last_name' => 'Cruz', 'date_of_birth' => '2018-01-01']);
    (new CreateDuplicateCandidatesJob($new))->handle();
    $admin = staffMember(RoleEnum::Admin, atChurch($church));

    foreach ([$new, $old] as $family) {
        actingAs($admin)->get("/ccf/families/{$family->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->has('duplicates', 1)->where('duplicates.0.reason_label', 'Same name and birthday'));
    }
});

it('shows a family no possible duplicates its viewer may not answer, nor answered ones @story#93', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house, 'new' => $new, 'old' => $old] = dupChurch();

    // The House admin sees the existing family, but may not edit the new one, so cannot answer.
    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get("/ccf/families/{$old->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->has('duplicates', 0));
    // A Lifeshaper never answers.
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))->get("/ccf/families/{$new->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->has('duplicates', 0));

    DuplicateCandidate::query()->update(['status' => DuplicateStatusEnum::Dismissed]);
    actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->get("/ccf/families/{$new->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->has('duplicates', 0));
});

it('has no possible-duplicates page of its own: the families list\'s filter finds them', function (): void {
    ['church' => $church] = dupChurch();

    actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->get('/ccf/duplicates')->assertNotFound();
});

it('dismisses a pair, records who, and notes it on the new family @story#72', function (): void {
    ['church' => $church, 'new' => $new] = dupChurch();
    $admin = staffMember(RoleEnum::Admin, atChurch($church));
    $pair = DuplicateCandidate::query()->sole();

    actingAs($admin)->put("/ccf/duplicates/{$pair->id}", ['status' => 'dismissed'])->assertRedirect()->assertSessionHas('success');

    $pair->refresh();
    expect($pair->status)->toBe(DuplicateStatusEnum::Dismissed)
        ->and($pair->resolved_by)->toBe($admin->id)
        ->and($pair->resolved_at)->not->toBeNull()
        ->and(Note::query()->where('noteable_type', 'family')->where('noteable_id', $new->id)->sole()->body)
        ->toBe('Possible duplicate of Maria Santos (same phone): not the same family.');
});

it('confirms a pair as the same family — flagged for the merge, nothing merged @story#72', function (): void {
    ['church' => $church] = dupChurch();
    $pair = DuplicateCandidate::query()->sole();

    actingAs(staffMember(RoleEnum::Admin, atChurch($church)))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'confirmed'])->assertSessionHas('success');

    expect($pair->fresh()?->status)->toBe(DuplicateStatusEnum::Confirmed)
        ->and(Family::query()->count())->toBe(2);
});

it('refuses to review a pair outside the reviewer\'s satellite, or one already reviewed @story#73', function (): void {
    ['church' => $church, 'house' => $house] = dupChurch();
    $pair = DuplicateCandidate::query()->sole();

    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'dismissed'])->assertNotFound();

    $pair->forceFill(['status' => DuplicateStatusEnum::Dismissed])->save();
    actingAs(staffMember(RoleEnum::Admin, atChurch($church)))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'confirmed'])->assertNotFound();
});

it('only takes "dismissed" or "confirmed" @story#73', function (): void {
    ['church' => $church] = dupChurch();
    $pair = DuplicateCandidate::query()->sole();

    actingAs(staffMember(RoleEnum::Admin, atChurch($church)))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'open'])->assertSessionHasErrors('status');
});

it('flags a child added to an older family that matches a newer family\'s child, the added child to answer for', function (): void {
    ['church' => $church, 'htc' => $htc, 'new' => $new, 'old' => $old] = dupChurch();
    $first = childOf($new, ['first_name' => 'Ana', 'last_name' => 'Santos', 'date_of_birth' => '2018-01-01']);

    actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->post("/ccf/families/{$old->id}/children", [
        'first_name' => 'Ana', 'last_name' => 'Santos', 'nickname' => 'Ana', 'gender' => 'girl', 'date_of_birth' => '2018-01-01', 'has_allergies' => false,
    ])->assertSessionHas('success');

    $added = $old->children()->latest('id')->firstOrFail();
    expect(DuplicateCandidate::query()->where('subject_type', 'child')->sole()->only(['subject_id', 'candidate_id']))
        ->toBe(['subject_id' => $added->id, 'candidate_id' => $first->id]);
});
