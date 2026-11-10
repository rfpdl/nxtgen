<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Console\Features\Registry\ProposeRegistryImportFeature;
use Illuminate\Console\Command;
use Lucid\Bus\ServesFeatures;

/**
 * Step 1 of loading the existing Google Sheet (plan §7): read the Registry tab CSV and write a review file.
 * Nothing is written to the database. The CSV is personal data — keep it in storage/imports/ (git-ignored). The work
 * is a Feature served from here (TODO #42); this only tells the operator what happened.
 */
final class RegistryProposeCommand extends Command
{
    use ServesFeatures;

    protected $signature = 'nxtgen:registry-propose {csv : Registry tab exported as CSV} {--out= : Review file to write (.xlsx)}';

    protected $description = 'Propose families from the existing registry sheet, for a human to review';

    public function handle(): int
    {
        $summary = $this->serve(ProposeRegistryImportFeature::class, [
            'csv' => (string) $this->argument('csv'),
            'out' => (string) ($this->option('out') ?: storage_path('imports/registry-review.xlsx')),
        ]);

        $this->info(sprintf('%d rows → %d families, %d duplicates dropped, %d quarantined.', $summary->rows, $summary->families, $summary->droppedDuplicates, $summary->quarantined));
        $this->info("Review file: {$summary->path}");

        return self::SUCCESS;
    }
}
