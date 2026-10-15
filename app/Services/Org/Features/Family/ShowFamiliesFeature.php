<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Data\Models\Family;
use App\Domains\Family\DataObjects\FamilyListRowData;
use App\Domains\Family\Jobs\GetFamiliesJob;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BaseOrgFeature<Response> */
final class ShowFamiliesFeature extends BaseOrgFeature
{
    public function handle(): Response
    {
        $scope = $this->scope();
        $unconfirmed = request()->boolean('unconfirmed');

        $families = $this->run(new GetFamiliesJob(
            organizationId: $this->organization()->id,
            branchIds: $scope->visibleBranchIds(),
            unconfirmedOnly: $unconfirmed,
        ));

        return Inertia::render('Org/Families/Index', [
            'church' => ['name' => $this->organization()->name, 'slug' => $this->organization()->slug],
            'families' => array_values($families->map(fn (Family $f) => FamilyListRowData::fromModel($f, fullEmail: $scope->canEditFamily($f)))->all()),
            'filters' => ['unconfirmed' => $unconfirmed],
        ]);
    }
}
