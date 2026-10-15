<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Domains\Family\DataObjects\FamilyAdminData;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Domains\Family\Jobs\GetFamilyNotesJob;
use App\Domains\Organization\Jobs\GetBranchesJob;
use App\Enums\AccessRowEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A family's page for staff who can see it. The edit details (family + main parent) are sent ONLY to someone
 * who may edit the family — a Lifeshaper sees the family, not the forms.
 *
 * @extends BaseOrgFeature<Response>
 */
#[AccessRow(AccessRowEnum::ViewFamily)]
final class ShowFamilyFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(): Response
    {
        $org = $this->organization();
        $family = $this->run(new GetFamilyByIdJob(organizationId: $org->id, id: $this->familyId));
        abort_unless($this->scope()->canSeeFamily($family), 404);
        $canEdit = $this->scope()->canEditFamily($family);
        $primary = $family->primaryGuardian;

        return Inertia::render('Org/Families/Show', [
            'church' => ['name' => $org->name, 'slug' => $org->slug],
            'family' => FamilyAdminData::fromModel($family, canEdit: $canEdit),
            'notes' => $this->run(new GetFamilyNotesJob(family: $family)),
            'details' => $canEdit ? FamilyDetailsData::fromModel($family) : null,
            'primary' => $canEdit && $primary !== null ? GuardianEditData::fromModel($primary) : null,
            'satellites' => $canEdit ? $this->run(new GetBranchesJob(organization: $org)) : [],
            'relationships' => RelationshipEnum::toFrontend(),
            'genders' => GenderEnum::toFrontend(),
        ]);
    }
}
