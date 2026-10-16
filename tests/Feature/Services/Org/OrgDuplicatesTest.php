<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Data\Models\Organization;
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
 * merge)"): candidates are written at registration; staff who may edit the NEW family review them. Nothing is
 * ever merged here.
 */

/** @return array{org: Organization, htc: Branch, house: Branch, new: Family, old: Family} */
function dupChurch(): array
{
    $org = church(['slug' => 'ccf']);
    $htc = satelliteOf($org, ['name' => 'Holy Trinity Church']);
    $house = satelliteOf($org, ['name' => 'House Church']);
    $old = dupFamily($house, 'Maria', 'maria@old.com');
    $new = dupFamily($htc, 'Mary', 'mary@new.com');
    (new CreateDuplicateCandidatesJob($new))->handle();

    return ['org' => $org, 'htc' => $htc, 'house' => $house, 'new' => $new, 'old' => $old];
}

function dupFamily(Branch $branch, string $name, string $email): Family
{
    $family = familyAt($branch);
    $user = User::factory()->create(['email' => $email]);
    $g = guardianOf($family, $user, ['first_name' => $name, 'last_name' => 'Santos', 'phone' => '+971 50 123 4567']);
    childOf($family, ['nickname' => "{$name}'s kid"]);

    return $family;
}

it('lists open possible duplicates side by side, with masked emails', function (): void {
    ['org' => $org] = dupChurch();

    actingAs(staffMember(RoleEnum::Admin, atChurch($org)))->get('/ccf/duplicates')
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

    actingAs(staffMember(RoleEnum::Admin, atSatellite($htc)))->get('/ccf/duplicates')
        ->assertInertia(fn (AssertableInertia $p) => $p->has('duplicates', 1));
    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get('/ccf/duplicates')
        ->assertInertia(fn (AssertableInertia $p) => $p->has('duplicates', 0));
});

it('is not there for a Lifeshaper', function (): void {
    ['org' => $org] = dupChurch();

    actingAs(staffMember(RoleEnum::Lifeshaper, atChurch($org)))->get('/ccf/duplicates')->assertNotFound();
});

it('dismisses a pair, records who, and notes it on the new family', function (): void {
    ['org' => $org, 'new' => $new] = dupChurch();
    $admin = staffMember(RoleEnum::Admin, atChurch($org));
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

    actingAs(staffMember(RoleEnum::Admin, atChurch($org)))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'confirmed'])->assertSessionHas('success');

    expect($pair->fresh()?->status)->toBe(DuplicateStatusEnum::Confirmed)
        ->and(Family::query()->count())->toBe(2);
});

it('refuses to review a pair outside the reviewer\'s satellite, or one already reviewed', function (): void {
    ['org' => $org, 'house' => $house] = dupChurch();
    $pair = DuplicateCandidate::query()->sole();

    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'dismissed'])->assertNotFound();

    $pair->forceFill(['status' => DuplicateStatusEnum::Dismissed])->save();
    actingAs(staffMember(RoleEnum::Admin, atChurch($org)))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'confirmed'])->assertNotFound();
});

it('only takes "dismissed" or "confirmed"', function (): void {
    ['org' => $org] = dupChurch();
    $pair = DuplicateCandidate::query()->sole();

    actingAs(staffMember(RoleEnum::Admin, atChurch($org)))
        ->put("/ccf/duplicates/{$pair->id}", ['status' => 'open'])->assertSessionHasErrors('status');
});
