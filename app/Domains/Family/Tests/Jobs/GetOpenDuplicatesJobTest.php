<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\DuplicateCandidate;
use App\Domains\Family\Jobs\GetOpenDuplicatesJob;
use App\Enums\DuplicateReasonEnum;
use App\Enums\DuplicateStatusEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* This church's unanswered possible duplicates, newest first, with both sides' families ready for the review screen. */

it('lists only this church\'s open pairs, newest first', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    [$a, $b, $c] = [familyAt($satellite), familyAt($satellite), familyAt($satellite)];
    $older = DuplicateCandidate::query()->create([
        'church_id' => $church->id, 'subject_type' => 'family', 'subject_id' => $b->id, 'candidate_id' => $a->id,
        'reason' => DuplicateReasonEnum::SamePhone, 'status' => DuplicateStatusEnum::Open,
    ]);
    $newer = DuplicateCandidate::query()->create([
        'church_id' => $church->id, 'subject_type' => 'family', 'subject_id' => $c->id, 'candidate_id' => $a->id,
        'reason' => DuplicateReasonEnum::SamePhone, 'status' => DuplicateStatusEnum::Open,
    ]);
    DuplicateCandidate::query()->create([
        'church_id' => $church->id, 'subject_type' => 'family', 'subject_id' => $c->id, 'candidate_id' => $b->id,
        'reason' => DuplicateReasonEnum::SamePhone, 'status' => DuplicateStatusEnum::Dismissed,
    ]);
    $elsewhere = church();
    $other = satelliteOf($elsewhere);
    DuplicateCandidate::query()->create([
        'church_id' => $elsewhere->id, 'subject_type' => 'family', 'subject_id' => familyAt($other)->id, 'candidate_id' => familyAt($other)->id,
        'reason' => DuplicateReasonEnum::SamePhone, 'status' => DuplicateStatusEnum::Open,
    ]);

    $open = (new GetOpenDuplicatesJob(church: $church))->handle();

    expect($open->pluck('id')->all())->toBe([$newer->id, $older->id]);
});

it('loads a child pair\'s families with their home satellite and children', function (): void {
    $church = church();
    $satellite = satelliteOf($church, ['name' => 'CCF DXB']);
    $first = childOf(familyAt($satellite));
    $second = childOf(familyAt($satellite));
    DuplicateCandidate::query()->create([
        'church_id' => $church->id, 'subject_type' => 'child', 'subject_id' => $second->id, 'candidate_id' => $first->id,
        'reason' => DuplicateReasonEnum::SameNameDob, 'status' => DuplicateStatusEnum::Open,
    ]);

    $pair = (new GetOpenDuplicatesJob(church: $church))->handle()->sole();
    $subject = $pair->subject;
    assert($subject instanceof Child);
    $family = $subject->family;

    expect($pair->relationLoaded('subject') && $pair->relationLoaded('candidate'))->toBeTrue()
        ->and($subject->relationLoaded('family'))->toBeTrue()
        ->and($family->relationLoaded('homeSatellite') && $family->relationLoaded('children'))->toBeTrue()
        ->and($family->homeSatellite->name)->toBe('CCF DXB');
});
