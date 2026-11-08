<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Domains\Family\Jobs\GetFamiliesJob;
use App\Domains\Report\Jobs\CreateFamiliesSpreadsheetJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\FamilyListQuery;
use App\Services\Church\Features\BaseChurchFeature;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The same families the list shows, as Excel. Full emails only for an admin of this satellite (who may edit its families).
 *
 * @extends BaseChurchFeature<BinaryFileResponse>
 */
#[UserStory(
    title: 'Download the families list',
    persona: 'staff',
    happyPaths: ['story#45' => 'Staff download what the list shows as an Excel file, every page'],
)]
#[AccessRow(AccessRowEnum::FamilyList)]
final class ExportFamiliesFeature extends BaseChurchFeature
{
    public function handle(): BinaryFileResponse
    {
        $scope = $this->scope();
        // The same filters as the screen, so the download is what the list shows — every page of it (no `page`).
        $families = $this->run(new GetFamiliesJob(church: $this->church(), satelliteIds: $scope->visibleSatelliteIds(), filters: FamilyListQuery::from(request())));
        $path = $this->run(new CreateFamiliesSpreadsheetJob(families: $families->items, fullEmail: $scope->canUseFullAdmin()));   // an admin here: their satellite's families (DECISIONS 2026-11-08)

        return response()->download($path, 'nxtgen-families-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
