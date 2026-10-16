<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * How many people are church admin here, counted under a lock so two admins removing each other at the same
 * moment cannot leave the church with none.
 *
 * @extends BaseJob<int>
 */
final class GetChurchAdminCountJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    public function handle(): int
    {
        return RoleAssignment::query()
            ->where('church_id', $this->church->id)
            ->where('role', RoleEnum::Admin)
            ->where('scope_type', ScopeTypeEnum::Church)
            ->where('scope_id', $this->church->id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->count();
    }
}
