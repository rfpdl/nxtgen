<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Data\Models\Family;
use App\Domains\Family\DataObjects\FamilySearchResultData;
use App\Domains\Family\Jobs\SearchFamiliesJob;
use App\Domains\Organization\Jobs\GetBranchesJob;
use App\Enums\AccessRowEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Registering for a parent: SEARCH FIRST across the whole church (plan §7.2), then add a child to a found
 * family or register a new one.
 *
 * @extends BaseOrgFeature<Response>
 */
#[AccessRow(AccessRowEnum::DoorSearch)]
final class ShowRegisterFeature extends BaseOrgFeature
{
    public function handle(): Response
    {
        $org = $this->organization();
        $scope = $this->scope();
        $query = trim(request()->string('q')->value());

        $results = $this->run(new SearchFamiliesJob(organizationId: $org->id, query: $query));

        return Inertia::render('Org/Families/Register', [
            'church' => ['name' => $org->name, 'slug' => $org->slug],
            'query' => $query,
            'results' => array_values($results->map(fn (Family $f) => FamilySearchResultData::fromModel($f, canOpen: $scope->canSeeFamily($f)))->all()),
            'satellites' => $this->run(new GetBranchesJob(organization: $org)),
            'relationships' => RelationshipEnum::toFrontend(),
            'genders' => GenderEnum::toFrontend(),
        ]);
    }
}
