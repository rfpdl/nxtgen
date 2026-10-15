<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Domains\Family\DataObjects\FamilyAdminData;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Enums\GenderEnum;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BaseOrgFeature<Response> */
final class ShowFamilyFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(): Response
    {
        $org = $this->organization();
        $family = $this->run(new GetFamilyByIdJob(organizationId: $org->id, id: $this->familyId));
        abort_unless($this->scope()->canSeeFamily($family), 404);

        return Inertia::render('Org/Families/Show', [
            'church' => ['name' => $org->name, 'slug' => $org->slug],
            'family' => FamilyAdminData::fromModel($family, canEdit: $this->scope()->canEditFamily($family)),
            'genders' => GenderEnum::toFrontend(),
        ]);
    }
}
