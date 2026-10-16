<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Family\DataObjects\FamilyAdminData;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Domains\Family\Jobs\GetFamilyNotesJob;
use App\Domains\Group\Jobs\GetGroupsJob;
use App\Enums\AccessRowEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A family's page for staff who can see it. The edit details (family + main parent) are sent ONLY to someone
 * who may edit the family — a Lifeshaper sees the family, not the forms.
 *
 * @extends BaseChurchFeature<Response>
 */
#[AccessRow(AccessRowEnum::ViewFamily)]
final class ShowFamilyFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(): Response
    {
        $church = $this->church();
        $family = $this->run(new GetFamilyByIdJob(churchId: $church->id, id: $this->familyId));
        abort_unless($this->scope()->canSeeFamily($family), 404);
        $canEdit = $this->scope()->canEditFamily($family);
        $primary = $family->primaryGuardian;

        return Inertia::render('Church/Families/Show', [
            'church' => ['name' => $church->name, 'slug' => $church->slug],
            'family' => FamilyAdminData::fromModel($family, canEdit: $canEdit),
            'notes' => $this->run(new GetFamilyNotesJob(family: $family)),
            'details' => $canEdit ? FamilyDetailsData::fromModel($family) : null,
            'primary' => $canEdit && $primary !== null ? GuardianEditData::fromModel($primary) : null,
            'satellites' => $canEdit ? $this->run(new GetSatellitesJob(church: $church)) : [],
            'relationships' => RelationshipEnum::toFrontend(),
            'canSetGroup' => $this->scope()->canSetGroup(),
            'groups' => $this->scope()->canSetGroup() ? $this->run(new GetGroupsJob(churchId: $church->id)) : [],
            'genders' => GenderEnum::toFrontend(),
        ]);
    }
}
