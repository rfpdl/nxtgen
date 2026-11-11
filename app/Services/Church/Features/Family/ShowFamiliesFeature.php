<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Data\Models\Family;
use App\Domains\Family\DataObjects\FamilyListRowData;
use App\Domains\Family\DataObjects\ListFiltersData;
use App\Domains\Family\Jobs\GetFamiliesJob;
use App\Domains\Level\DataObjects\LevelOptionData;
use App\Domains\Level\Jobs\GetLevelsJob;
use App\Enums\AccessRowEnum;
use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\FamilyListQuery;
use App\Services\Church\DataObjects\FamiliesPageData;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The registration list, with search and filters (plan §6): the families of the satellite in the address only
 * (DECISIONS 2026-11-08); a filter can only narrow it.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See the families list',
    persona: 'staff',
    happyPaths: ['story#43' => 'Staff see the Families of the Satellite in the address, with emails masked; searched and filtered'],
    unhappyPaths: ['story#44' => 'Someone with no Role in this church sees nothing, and filters never widen a view'],
)]
#[AccessRow(AccessRowEnum::FamilyList)]
final class ShowFamiliesFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        $filters = FamilyListQuery::from(request());

        $page = $this->run(new GetFamiliesJob(church: $church, satelliteIds: $scope->visibleSatelliteIds(), filters: $filters, page: request()->integer('page', 1)));

        return Inertia::render('Church/Families/Index', new FamiliesPageData(
            families: array_map(fn (Family $f) => FamilyListRowData::fromModel($f, fullEmail: $scope->canEditFamily($f), mayResend: $scope->canResendLink($f)), $page->items),
            pagination: $page->meta(),
            filters: ListFiltersData::fromFamilyFilters($filters),
            options: [
                'levels' => [...$this->run(new GetLevelsJob), LevelOptionData::none()],
            ],
            enums: ['child_status' => ChildStatusEnum::toFrontend()],
        ));
    }
}
