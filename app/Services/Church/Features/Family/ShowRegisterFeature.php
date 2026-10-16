<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Data\Models\Family;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Family\DataObjects\FamilySearchResultData;
use App\Domains\Family\Jobs\SearchFamiliesJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Support\FamilyFormEnums;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Registering for a parent: SEARCH FIRST across the whole church (plan §7.2), then add a child to a found
 * family or register a new one.
 *
 * @extends BaseChurchFeature<Response>
 */
#[AccessRow(AccessRowEnum::DoorSearch)]
final class ShowRegisterFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        $query = trim(request()->string('q')->value());

        $results = $this->run(new SearchFamiliesJob(church: $church, query: $query));

        return Inertia::render('Church/Families/Register', [
            'query' => $query,
            'results' => array_values($results->map(fn (Family $f) => FamilySearchResultData::fromModel($f, canOpen: $scope->canSeeFamily($f)))->all()),
            'satellites' => $this->run(new GetSatellitesJob(church: $church)),
            'enums' => FamilyFormEnums::toFrontend(),
        ]);
    }
}
