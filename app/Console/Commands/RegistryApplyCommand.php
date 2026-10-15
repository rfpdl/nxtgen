<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\Models\Organization;
use App\Domains\Registry\Jobs\GetApprovedRegistryRowsJob;
use App\Operations\Registry\ApplyRegistryImportOperation;
use Illuminate\Console\Command;

/** Step 2 (plan §7): load the REVIEWED file. Sends no emails; safe to run twice. */
final class RegistryApplyCommand extends Command
{
    protected $signature = 'nxtgen:registry-apply {xlsx : The reviewed file} {--org= : Church slug, e.g. ccf}';

    protected $description = 'Load the reviewed registry families into a church';

    public function handle(): int
    {
        $org = Organization::query()->where('slug', (string) $this->option('org'))->first();
        if ($org === null) {
            $this->error('Unknown church. Pass --org=<slug>.');

            return self::FAILURE;
        }

        $approval = (new GetApprovedRegistryRowsJob(path: (string) $this->argument('xlsx')))->handle();
        $result = (new ApplyRegistryImportOperation(organization: $org, groups: $approval->groups))->handle();
        $problems = [...$approval->problems, ...$result->problems];

        $this->info("Families created: {$result->familiesCreated}. Children loaded: {$result->childrenCreated}. Already loaded, skipped: {$result->childrenSkipped}.");
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
