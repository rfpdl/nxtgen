<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Domains\Family\Jobs\GetFamilyOpenDuplicatesJob;
use App\Enums\DuplicateReasonEnum;
use App\Enums\DuplicateStatusEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A family's unanswered possible duplicates — on either side, as a family or through one of its children — newest first, both sides ready for the family page. */

function pairOf(string $type, Family|Child $subject, Family|Child $candidate, DuplicateStatusEnum $status = DuplicateStatusEnum::Open): DuplicateCandidate
{
    return DuplicateCandidate::query()->create([
        'church_id' => $subject->church_id, 'subject_type' => $type, 'subject_id' => $subject->id, 'candidate_id' => $candidate->id,
        'reason' => $type === 'family' ? DuplicateReasonEnum::SamePhone : DuplicateReasonEnum::SameNameDob, 'status' => $status,
    ]);
}

it('lists the open pairs the family is on, either side, newest first', function (): void {
    $satellite = satelliteOf(church());
    [$a, $b, $c, $d] = [familyAt($satellite), familyAt($satellite), familyAt($satellite), familyAt($satellite)];
    $asCandidate = pairOf('family', $b, $a);
    $asSubject = pairOf('family', $a, $c);
    pairOf('family', $a, $d, DuplicateStatusEnum::Dismissed);
    pairOf('family', $c, $b);

    $open = (new GetFamilyOpenDuplicatesJob(family: $a))->handle();

    expect($open->pluck('id')->all())->toBe([$asSubject->id, $asCandidate->id]);
});

it('lists the pairs of the family\'s children, either side', function (): void {
    $satellite = satelliteOf(church());
    [$a, $b, $c] = [familyAt($satellite), familyAt($satellite), familyAt($satellite)];
    [$mine, $theirs, $unrelated] = [childOf($a), childOf($b), childOf($c)];
    $asCandidate = pairOf('child', $theirs, $mine);
    $asSubject = pairOf('child', $mine, $unrelated);
    pairOf('child', $unrelated, $theirs);
    // A family pair keyed by an id that happens to equal a child's is not this family's.
    pairOf('family', $b, $c);

    $open = (new GetFamilyOpenDuplicatesJob(family: $a))->handle();

    expect($open->pluck('id')->all())->toBe([$asSubject->id, $asCandidate->id]);
});

it('loads a child pair\'s families with their home satellite and children', function (): void {
    $satellite = satelliteOf(church(), ['name' => 'CCF DXB']);
    $first = childOf(familyAt($satellite));
    $second = childOf(familyAt($satellite));
    pairOf('child', $second, $first);

    $pair = (new GetFamilyOpenDuplicatesJob(family: $first->family))->handle()->sole();
    $subject = $pair->subject;
    assert($subject instanceof Child);
    $family = $subject->family;

    expect($pair->relationLoaded('subject') && $pair->relationLoaded('candidate'))->toBeTrue()
        ->and($subject->relationLoaded('family'))->toBeTrue()
        ->and($family->relationLoaded('homeSatellite') && $family->relationLoaded('children'))->toBeTrue()
        ->and($family->homeSatellite->name)->toBe('CCF DXB');
});
