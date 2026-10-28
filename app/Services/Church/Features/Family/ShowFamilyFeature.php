<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Data\Models\DuplicateCandidate;
use App\Domains\CheckIn\DataObjects\ChildAttendanceData;
use App\Domains\CheckIn\Jobs\GetFamilyCheckInsJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Family\DataObjects\DuplicatePairData;
use App\Domains\Family\DataObjects\FamilyAdminData;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\GetFamilyNotesJob;
use App\Domains\Family\Jobs\GetFamilyOpenDuplicatesJob;
use App\Domains\Level\Jobs\GetLevelsJob;
use App\Enums\AccessRowEnum;
use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\FamilyFormEnums;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A family's page for staff who can see it. The edit details (family, main parent, other guardians) are sent ONLY to someone
 * who may edit the family — a Lifeshaper sees the family, not the forms. Its open possible duplicates are listed for
 * someone who may answer them (an admin who may edit the newer family), now that there is no Duplicates tab
 * (DECISIONS 2026-10-17, after first look); each side in the church-wide lite view. Each child's attendance, newest
 * first (slice 3) — for staff only.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'Open a family\'s page',
    persona: 'staff',
    happyPaths: [
        'story#50' => 'Staff open a Family they can see, with its notes, newest first',
        'story#92' => 'An Admin who may answer sees the Family\'s open possible duplicates, either side, to answer there',
        'story#126' => 'Staff see each child\'s attendance, newest first — date, class, satellite, Squad and present or not marked — never an undone Check-in',
    ],
    unhappyPaths: [
        'story#51' => 'A Family they cannot see is not found',
        'story#93' => 'Nobody sees a possible duplicate they may not answer, nor an answered one',
        'story#127' => 'A child\'s attendance is for staff who can see the family: a parent\'s own family page never carries it',
    ],
)]
#[AccessRow(AccessRowEnum::ViewFamily)]
final class ShowFamilyFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        $family = $this->scopedFamily($this->familyId, $scope->canSeeFamily(...));
        $canEdit = $scope->canEditFamily($family);
        $primary = $family->primaryGuardian;

        return Inertia::render('Church/Families/Show', [
            'family' => FamilyAdminData::fromModel($family, canEdit: $canEdit),
            // Each child's attendance (slice 3): staff only — the parent's own page never carries it.
            'attendance' => ChildAttendanceData::forChildren(
                $family->children,
                $this->run(new GetFamilyCheckInsJob(family: $family)),
                $this->run(new GetChurchTodayJob(church: $church))->year,
            ),
            'notes' => $this->run(new GetFamilyNotesJob(family: $family)),
            'duplicates' => $scope->canReviewDuplicates() ? array_values($this->run(new GetFamilyOpenDuplicatesJob(family: $family))
                ->filter(fn (DuplicateCandidate $p): bool => $scope->canEditFamily(DuplicateCandidate::familyOf($p->subject)))
                ->map(fn (DuplicateCandidate $p): DuplicatePairData => DuplicatePairData::fromModel(
                    $p,
                    canOpenSubject: $scope->canSeeFamily(DuplicateCandidate::familyOf($p->subject)),
                    canOpenCandidate: $scope->canSeeFamily(DuplicateCandidate::familyOf($p->candidate)),
                ))->all()) : [],
            'details' => $canEdit ? FamilyDetailsData::fromModel($family) : null,
            'primary' => $canEdit && $primary !== null ? GuardianEditData::fromModel($primary) : null,
            'other_guardians' => $canEdit ? GuardianEditData::othersOf($family) : [],
            'satellites' => $canEdit ? $this->run(new GetSatellitesJob(church: $church)) : [],
            'can_set_level' => $scope->canSetLevel(),
            'levels' => $scope->canSetLevel() ? $this->run(new GetLevelsJob) : [],
            'enums' => [...FamilyFormEnums::toFrontend(), 'child_status' => ChildStatusEnum::toFrontend()],
        ]);
    }
}
