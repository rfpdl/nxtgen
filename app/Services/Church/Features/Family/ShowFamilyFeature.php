<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Family\DataObjects\FamilyAdminData;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\GetFamilyNotesJob;
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
 * A family's page for staff who can see it. The edit details (family + main parent) are sent ONLY to someone
 * who may edit the family — a Lifeshaper sees the family, not the forms.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'Open a family\'s page',
    persona: 'staff',
    happyPaths: ['story#50' => 'Staff open a Family they can see, with its notes, newest first'],
    unhappyPaths: ['story#51' => 'A Family they cannot see is not found'],
)]
#[AccessRow(AccessRowEnum::ViewFamily)]
final class ShowFamilyFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(): Response
    {
        $church = $this->church();
        $family = $this->scopedFamily($this->familyId, $this->scope()->canSeeFamily(...));
        $canEdit = $this->scope()->canEditFamily($family);
        $primary = $family->primaryGuardian;

        return Inertia::render('Church/Families/Show', [
            'family' => FamilyAdminData::fromModel($family, canEdit: $canEdit),
            'notes' => $this->run(new GetFamilyNotesJob(family: $family)),
            'details' => $canEdit ? FamilyDetailsData::fromModel($family) : null,
            'primary' => $canEdit && $primary !== null ? GuardianEditData::fromModel($primary) : null,
            'satellites' => $canEdit ? $this->run(new GetSatellitesJob(church: $church)) : [],
            'can_set_level' => $this->scope()->canSetLevel(),
            'levels' => $this->scope()->canSetLevel() ? $this->run(new GetLevelsJob) : [],
            'enums' => [...FamilyFormEnums::toFrontend(), 'child_status' => ChildStatusEnum::toFrontend()],
        ]);
    }
}
