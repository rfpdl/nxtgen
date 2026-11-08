<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * How many active admins this satellite has (spec item 9: the last one cannot be removed). Locks the satellite's row
 * first, then its admins' rows: two admins removing each other at once queue on the satellite, so the second sees the
 * first's removal and is refused. Call it inside the transaction that removes the Role.
 *
 * @extends BaseJob<int>
 */
final class GetSatelliteAdminCountJob extends BaseJob
{
    public function __construct(private readonly Satellite $satellite) {}

    public function handle(): int
    {
        Satellite::query()->where('church_id', $this->satellite->church_id)->whereKey($this->satellite->id)->lockForUpdate()->sole();

        return RoleAssignment::query()
            ->where('church_id', $this->satellite->church_id)
            ->where('role', RoleEnum::Admin)
            ->where('scope_type', ScopeTypeEnum::Satellite)
            ->where('scope_id', $this->satellite->id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->count();
    }
}
