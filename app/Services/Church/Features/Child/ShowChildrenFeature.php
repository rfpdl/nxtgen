<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Data\Models\Child;
use App\Domains\Family\DataObjects\ChildListRowData;
use App\Domains\Family\DataObjects\ListFiltersData;
use App\Domains\Family\Jobs\GetChildrenJob;
use App\Domains\Level\DataObjects\LevelOptionData;
use App\Domains\Level\Jobs\GetLevelsJob;
use App\Enums\AccessRowEnum;
use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\ChildListQuery;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The children list (plan §6 "families/children lists"): children of the satellite in the address (DECISIONS 2026-11-08), with
 * Level, age, status and an allergy flag; search and filters that only narrow.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See the children list',
    persona: 'staff',
    happyPaths: ['story#58' => 'Staff see the Children they may, searched and filtered by Level and status'],
    unhappyPaths: ['story#59' => 'Someone with no Role in this church sees nothing'],
)]
#[AccessRow(AccessRowEnum::FamilyList)]
final class ShowChildrenFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        $filters = ChildListQuery::from(request());

        $page = $this->run(new GetChildrenJob(church: $church, satelliteIds: $scope->visibleSatelliteIds(), filters: $filters, page: request()->integer('page', 1)));

        return Inertia::render('Church/Children/Index', [
            'children' => array_map(fn (Child $c): ChildListRowData => ChildListRowData::fromModel($c), $page->items),
            'pagination' => $page->meta(),
            'filters' => ListFiltersData::fromChildFilters($filters),
            'options' => [
                'levels' => [...$this->run(new GetLevelsJob), LevelOptionData::none()],
            ],
            'enums' => ['child_status' => ChildStatusEnum::toFrontend()],
        ]);
    }
}
