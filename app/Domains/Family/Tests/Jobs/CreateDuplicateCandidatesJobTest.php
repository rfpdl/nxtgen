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
