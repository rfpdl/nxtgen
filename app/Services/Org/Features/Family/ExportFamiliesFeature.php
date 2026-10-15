<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Domains\Family\Jobs\GetFamiliesJob;
use App\Domains\Report\Jobs\CreateFamiliesSpreadsheetJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Data\FamilyListQuery;
use App\Services\Org\Features\BaseOrgFeature;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The same families the list shows, as Excel. Full emails only for a church admin (who may edit all).
 *
 * @extends BaseOrgFeature<BinaryFileResponse>
 */
#[AccessRow(AccessRowEnum::FamilyList)]
final class ExportFamiliesFeature extends BaseOrgFeature
{
    public function handle(): BinaryFileResponse
    {
        $scope = $this->scope();
        // The same filters as the screen, so the download is what the list shows.
        $families = $this->run(new GetFamiliesJob(organizationId: $this->organization()->id, branchIds: $scope->visibleBranchIds(), filters: FamilyListQuery::from(request())));
        $path = $this->run(new CreateFamiliesSpreadsheetJob(families: $families, fullEmail: $scope->isChurchAdmin));

        return response()->download($path, 'nxtgen-families-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
