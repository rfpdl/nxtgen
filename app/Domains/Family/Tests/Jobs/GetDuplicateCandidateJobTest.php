<?php

declare(strict_types=1);

use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Satellite;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Domains\Family\Jobs\GetDuplicateCandidateJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* One possible-duplicate pair by id, only within this church, with both sides loaded. */

it('finds this church\'s pair with both families loaded', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $phoneFamily = function (Satellite $at): Family {
        $family = familyAt($at);
        Guardian::factory()->create(['family_id' => $family->id, 'phone' => '0501234567']);

        return $family;
    };
    $old = $phoneFamily($satellite);
    $new = $phoneFamily($satellite);
    (new CreateDuplicateCandidatesJob(family: $new))->handle();
    $pair = DuplicateCandidate::query()->sole();

    $found = (new GetDuplicateCandidateJob(church: $church, id: $pair->id))->handle();

    expect($found->relationLoaded('subject') && $found->relationLoaded('candidate'))->toBeTrue()
        ->and($found->subject->getKey())->toBe($new->id)
        ->and($found->candidate->getKey())->toBe($old->id);
});

it('refuses another church\'s pair', function (): void {
    $satellite = satelliteOf(church());
    Guardian::factory()->create(['family_id' => familyAt($satellite)->id, 'phone' => '0501234567']);
    $new = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $new->id, 'phone' => '0501234567']);
    (new CreateDuplicateCandidatesJob(family: $new))->handle();

    (new GetDuplicateCandidateJob(church: church(), id: DuplicateCandidate::query()->sole()->id))->handle();
})->throws(ModelNotFoundException::class);
