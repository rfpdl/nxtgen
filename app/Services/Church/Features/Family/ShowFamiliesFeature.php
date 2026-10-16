<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Data\Models\Family;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Family\DataObjects\FamilyListRowData;
use App\Domains\Family\DataObjects\ListFiltersData;
use App\Domains\Family\Jobs\GetFamiliesJob;
use App\Domains\Level\Jobs\GetLevelsJob;
use App\Enums\AccessRowEnum;
use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Support\ListPage;
use App\Services\Church\Data\FamilyListQuery;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The registration list, with search and filters (plan §6). Only satellites the viewer can see are offered,
 * and a filter can only narrow their scope.
 *
 * @extends BaseChurchFeature<Response>
 */
#[AccessRow(AccessRowEnum::FamilyList)]
final class ShowFamiliesFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        $filters = FamilyListQuery::from(request());
        $visible = $scope->visibleSatelliteIds();

        $families = $this->run(new GetFamiliesJob(church: $church, satelliteIds: $visible, filters: $filters));
        $page = ListPage::of($families, requested: request()->integer('page', 1));
        $satellites = array_values(array_filter(
            $this->run(new GetSatellitesJob(church: $church)),
            fn (SatelliteOptionData $s): bool => $visible === null || in_array($s->id, $visible, true),
        ));

        return Inertia::render('Church/Families/Index', [
            'families' => array_map(fn (Family $f) => FamilyListRowData::fromModel($f, fullEmail: $scope->canEditFamily($f)), $page->items),
            'pagination' => $page->meta(),
            'filters' => ListFiltersData::fromFamilyFilters($filters),
            'options' => [
                'satellites' => $satellites,
                'levels' => $this->run(new GetLevelsJob),
            ],
            'enums' => ['child_status' => ChildStatusEnum::toFrontend()],
        ]);
    }
}
