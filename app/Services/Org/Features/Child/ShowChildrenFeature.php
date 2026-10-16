<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Child;

use App\Data\Models\Child;
use App\Domains\Family\DataObjects\ChildListRowData;
use App\Domains\Family\Jobs\GetChildrenJob;
use App\Domains\Group\Jobs\GetGroupsJob;
use App\Domains\Organization\DataObjects\BranchOptionData;
use App\Domains\Organization\Jobs\GetBranchesJob;
use App\Enums\AccessRowEnum;
use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Support\ListPage;
use App\Services\Org\Data\ChildListQuery;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The children list (plan §6 "families/children lists"): children of the satellites the viewer can see, with
 * group, age, status and an allergy flag; search and filters that only narrow.
 *
 * @extends BaseOrgFeature<Response>
 */
#[AccessRow(AccessRowEnum::FamilyList)]
final class ShowChildrenFeature extends BaseOrgFeature
{
    public function handle(): Response
    {
        $org = $this->organization();
        $visible = $this->scope()->visibleBranchIds();
        $filters = ChildListQuery::from(request());

        $children = $this->run(new GetChildrenJob(organizationId: $org->id, branchIds: $visible, filters: $filters));
        $page = ListPage::of($children, requested: request()->integer('page', 1));

        return Inertia::render('Org/Children/Index', [
            'church' => ['name' => $org->name, 'slug' => $org->slug],
            'children' => array_map(fn (Child $c): ChildListRowData => ChildListRowData::fromModel($c), $page->items),
            'pagination' => $page->meta(),
            'filters' => ChildListQuery::toProps($filters),
            'options' => [
                'satellites' => array_values(array_filter(
                    $this->run(new GetBranchesJob(organization: $org)),
                    fn (BranchOptionData $b): bool => $visible === null || in_array($b->id, $visible, true),
                )),
                'groups' => $this->run(new GetGroupsJob(organizationId: $org->id)),
                'statuses' => ChildStatusEnum::toFrontend(),
            ],
        ]);
    }
}
