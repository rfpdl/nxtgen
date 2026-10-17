<?php

declare(strict_types=1);

use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Guardian;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Enums\DuplicateReasonEnum;
use App\Enums\DuplicateStatusEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Possible duplicates of a new family, for staff review: a shared phone, or a child with the same name and birthday. */

it('pairs families whose guardians share the last 9 phone digits, in the same church only', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $existing = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $existing->id, 'phone' => '+971 50 123 4567']);
    $elsewhere = familyAt(satelliteOf(church()));
    Guardian::factory()->create(['family_id' => $elsewhere->id, 'phone' => '0501234567']);
    $new = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $new->id, 'phone' => '050-123-4567']);

    $created = (new CreateDuplicateCandidatesJob(family: $new))->handle();

    $pair = DuplicateCandidate::query()->sole();
    expect($created)->toBe(1)
        ->and([$pair->subject_type, $pair->subject_id, $pair->candidate_id, $pair->church_id])->toBe(['family', $new->id, $existing->id, $church->id])
        ->and($pair->reason)->toBe(DuplicateReasonEnum::SamePhone)
        ->and($pair->status)->toBe(DuplicateStatusEnum::Open);
});

it('pairs children with the same name (any case) and birthday', function (): void {
    $satellite = satelliteOf(church());
    $twin = childOf(familyAt($satellite), ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-04']);
    childOf(familyAt($satellite), ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-05']);
    $new = familyAt($satellite);
    $child = childOf($new, ['first_name' => 'AMARA', 'last_name' => 'santos', 'date_of_birth' => '2019-03-04']);

    (new CreateDuplicateCandidatesJob(family: $new))->handle();

    $pair = DuplicateCandidate::query()->sole();
    expect([$pair->subject_type, $pair->subject_id, $pair->candidate_id])->toBe(['child', $child->id, $twin->id])
        ->and($pair->reason)->toBe(DuplicateReasonEnum::SameNameDob);
});

it('records a pair once, however often it runs', function (): void {
    $satellite = satelliteOf(church());
    Guardian::factory()->create(['family_id' => familyAt($satellite)->id, 'phone' => '0501234567']);
    $new = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $new->id, 'phone' => '0501234567']);

    (new CreateDuplicateCandidatesJob(family: $new))->handle();
    $again = (new CreateDuplicateCandidatesJob(family: $new))->handle();

    expect($again)->toBe(0)->and(DuplicateCandidate::query()->count())->toBe(1);
});

it('ignores a phone too short to compare', function (): void {
    $satellite = satelliteOf(church());
    Guardian::factory()->create(['family_id' => familyAt($satellite)->id, 'phone' => '123']);
    $new = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $new->id, 'phone' => '123']);

    expect((new CreateDuplicateCandidatesJob(family: $new))->handle())->toBe(0);
});

// It runs again when an older family changes (a guardian added, a child added): a pair already recorded the other
// way round — answered or not — is the same pair, and is not flagged again.
it('never records a pair again the other way round', function (): void {
    $satellite = satelliteOf(church());
    $older = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $older->id, 'phone' => '0501234567']);
    childOf($older, ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-04']);
    $newer = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $newer->id, 'phone' => '0501234567']);
    childOf($newer, ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-04']);
    (new CreateDuplicateCandidatesJob(family: $newer))->handle();
    DuplicateCandidate::query()->update(['status' => DuplicateStatusEnum::Dismissed]);

    $created = (new CreateDuplicateCandidatesJob(family: $older))->handle();

    expect($created)->toBe(0)
        ->and(DuplicateCandidate::query()->count())->toBe(2);
});

// Found from an older family (a guardian or a child added to it): the pair still names the NEWER record as the one to
// answer for, so it is the newer family's editor who answers, as at registration (plan §6).
it('records the newer family, or the child added later, as the one to answer for — whichever side it was found from', function (): void {
    $satellite = satelliteOf(church());
    $older = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $older->id, 'phone' => '0501234567']);
    $newer = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $newer->id, 'phone' => '0501234567']);
    $first = childOf($newer, ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-04']);
    // A child added to the older family later, the same as the newer family's.
    $later = childOf($older, ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-04']);

    (new CreateDuplicateCandidatesJob(family: $older))->handle();

    $pairs = DuplicateCandidate::query()->orderBy('subject_type')->get();
    expect($pairs->map(fn (DuplicateCandidate $p): array => [$p->subject_type, $p->subject_id, $p->candidate_id])->all())->toBe([
        ['child', $later->id, $first->id],
        ['family', $newer->id, $older->id],
    ]);
});
