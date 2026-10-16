<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Registry\Jobs\CreateRegistryReviewSpreadsheetJob;
use App\Domains\Registry\Jobs\GetRegistryProposalJob;
use App\Domains\Registry\Jobs\GetRegistryRowsJob;
use Illuminate\Console\Command;

/**
 * Step 1 of loading the existing Google Sheet (plan §7): read the Registry tab CSV and write a review file.
 * Nothing is written to the database. The CSV is personal data — keep it in storage/imports/ (git-ignored).
 */
final class RegistryProposeCommand extends Command
{
    protected $signature = 'nxtgen:registry-propose {csv : Registry tab exported as CSV} {--out= : Review file to write (.xlsx)}';

    protected $description = 'Propose families from the existing registry sheet, for a human to review';

    public function handle(): int
    {
        $csv = (string) $this->argument('csv');
        $out = (string) ($this->option('out') ?: storage_path('imports/registry-review.xlsx'));
        if (! is_dir(dirname($out))) {
            mkdir(dirname($out), 0775, true);
        }

        $rows = (new GetRegistryRowsJob(path: $csv))->handle();
        $proposal = (new GetRegistryProposalJob(rows: $rows))->handle();
        (new CreateRegistryReviewSpreadsheetJob(proposal: $proposal, path: $out, inputRows: count($rows)))->handle();

        $this->info(sprintf('%d rows → %d families, %d duplicates dropped, %d quarantined.', count($rows), count($proposal->families), $proposal->dropped_duplicates, count($proposal->quarantined)));
        $this->info("Review file: {$out}");

        return self::SUCCESS;
    }
}
