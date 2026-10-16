<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\Models\Church;
use App\Domains\Registry\Jobs\GetApprovedRegistryRowsJob;
use App\Operations\Registry\ApplyRegistryImportOperation;
use Illuminate\Console\Command;

/** Step 2 (plan §7): load the REVIEWED file. Sends no emails; safe to run twice. */
final class RegistryApplyCommand extends Command
{
    protected $signature = 'nxtgen:registry-apply {xlsx : The reviewed file} {--church= : Church slug, e.g. ccf}';

    protected $description = 'Load the reviewed registry families into a church';

    public function handle(): int
    {
        $church = Church::query()->where('slug', (string) $this->option('church'))->first();
        if ($church === null) {
            $this->error('Unknown church. Pass --church=<slug>.');

            return self::FAILURE;
        }

        $approval = (new GetApprovedRegistryRowsJob(path: (string) $this->argument('xlsx')))->handle();
        $result = (new ApplyRegistryImportOperation(church: $church, rowsByFamily: $approval->rows_by_family))->handle();
        $problems = [...$approval->problems, ...$result->problems];

        $this->info("Families created: {$result->families_created}. Children loaded: {$result->children_created}. Already loaded, skipped: {$result->children_skipped}.");
        if ($result->awaiting_claim !== []) {
            $count = count($result->awaiting_claim);
            $this->warn(($count === 1 ? '1 family waits' : "{$count} families wait").' for an existing login to confirm (no email was sent): '.implode(', ', $result->awaiting_claim));
            $this->line('Open each family and use "Send the confirmation link again" — they confirm from that email.');
        }
        foreach ($problems as $problem) {
            $this->warn($problem);
        }

        // Partial application is a failure: approved rows were left out and someone must look (review-spar B2).
        if ($problems !== []) {
            $this->error(count($problems).' problem(s) — some approved rows were NOT loaded. Fix them in the review file and run again.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
