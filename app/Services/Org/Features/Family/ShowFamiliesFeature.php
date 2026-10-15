<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Data\Models\Family;
use App\Domains\Family\DataObjects\FamilyListRowData;
use App\Domains\Family\Jobs\GetFamiliesJob;
use App\Domains\Group\Jobs\GetGroupsJob;
use App\Domains\Organization\DataObjects\BranchOptionData;
use App\Domains\Organization\Jobs\GetBranchesJob;
use App\Enums\AccessRowEnum;
use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Data\FamilyListQuery;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The registration list, with search and filters (plan §6). Only satellites the viewer can see are offered,
 * and a filter can only narrow their scope.
 *
 * @extends BaseOrgFeature<Response>
 */
#[AccessRow(AccessRowEnum::FamilyList)]
final class ShowFamiliesFeature extends BaseOrgFeature
{
    public function handle(): Response
    {
        $org = $this->organization();
        $scope = $this->scope();
        $filters = FamilyListQuery::from(request());
        $visible = $scope->visibleBranchIds();

        $families = $this->run(new GetFamiliesJob(organizationId: $org->id, branchIds: $visible, filters: $filters));
        $satellites = array_values(array_filter(
            $this->run(new GetBranchesJob(organization: $org)),
            fn (BranchOptionData $b): bool => $visible === null || in_array($b->id, $visible, true),
        ));

        return Inertia::render('Org/Families/Index', [
            'church' => ['name' => $org->name, 'slug' => $org->slug],
            'families' => array_values($families->map(fn (Family $f) => FamilyListRowData::fromModel($f, fullEmail: $scope->canEditFamily($f)))->all()),
            'filters' => FamilyListQuery::toProps($filters),
            'options' => [
                'satellites' => $satellites,
                'groups' => $this->run(new GetGroupsJob(organizationId: $org->id)),
                'statuses' => ChildStatusEnum::toFrontend(),
            ],
        ]);
    }
}
