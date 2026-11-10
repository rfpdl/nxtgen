<?php

declare(strict_types=1);

namespace App\Services\Console\Features\Registry;

use App\Domains\Registry\DataObjects\RegistryProposalSummaryData;
use App\Domains\Registry\Jobs\CreateRegistryReviewSpreadsheetJob;
use App\Domains\Registry\Jobs\GetRegistryProposalJob;
use App\Domains\Registry\Jobs\GetRegistryRowsJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Console\Features\BaseConsoleFeature;

/**
 * Step 1 of loading the existing Google Sheet (plan §7): read the Registry tab CSV and write a review file for a
 * person to approve. Nothing is written to the database. Served from `nxtgen:registry-propose` (TODO #42).
 *
 * @extends BaseConsoleFeature<RegistryProposalSummaryData>
 */
#[UserStory(
    title: 'Propose the registry for review',
    persona: 'platform admin at the terminal',
    happyPaths: ['story#211' => 'The registry CSV becomes a review file — families, quarantine, a balancing reconciliation — and nothing is loaded'],
)]
final class ProposeRegistryImportFeature extends BaseConsoleFeature
{
    public function __construct(
        private readonly string $csv,
        private readonly string $out,
    ) {}

    public function handle(): RegistryProposalSummaryData
    {
        $rows = $this->run(new GetRegistryRowsJob(path: $this->csv));
        $proposal = $this->run(new GetRegistryProposalJob(rows: $rows));
        $this->run(new CreateRegistryReviewSpreadsheetJob(proposal: $proposal, path: $this->out, inputRows: count($rows)));

        return new RegistryProposalSummaryData(
            rows: count($rows),
            families: count($proposal->families),
            droppedDuplicates: $proposal->droppedDuplicates,
            quarantined: count($proposal->quarantined),
            path: $this->out,
        );
    }
}
