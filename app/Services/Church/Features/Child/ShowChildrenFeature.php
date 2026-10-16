<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Data\Models\Child;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Family\DataObjects\ChildListRowData;
use App\Domains\Family\DataObjects\ListFiltersData;
use App\Domains\Family\Jobs\GetChildrenJob;
use App\Domains\Level\Jobs\GetLevelsJob;
use App\Enums\AccessRowEnum;
use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Support\ListPage;
use App\Services\Church\Data\ChildListQuery;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The children list (plan §6 "families/children lists"): children of the satellites the viewer can see, with
 * Level, age, status and an allergy flag; search and filters that only narrow.
 *
 * @extends BaseChurchFeature<Response>
 */
#[AccessRow(AccessRowEnum::FamilyList)]
final class ShowChildrenFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $visible = $this->scope()->visibleSatelliteIds();
        $filters = ChildListQuery::from(request());

        $children = $this->run(new GetChildrenJob(churchId: $church->id, satelliteIds: $visible, filters: $filters));
        $page = ListPage::of($children, requested: request()->integer('page', 1));

        return Inertia::render('Church/Children/Index', [
            'children' => array_map(fn (Child $c): ChildListRowData => ChildListRowData::fromModel($c), $page->items),
            'pagination' => $page->meta(),
            'filters' => ListFiltersData::fromChildFilters($filters),
            'options' => [
                'satellites' => array_values(array_filter(
                    $this->run(new GetSatellitesJob(church: $church)),
                    fn (SatelliteOptionData $s): bool => $visible === null || in_array($s->id, $visible, true),
                )),
                'levels' => $this->run(new GetLevelsJob),
            ],
            'enums' => ['child_status' => ChildStatusEnum::toFrontend()],
        ]);
    }
}
