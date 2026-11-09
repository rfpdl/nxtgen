<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * How many active admins each of this church's satellites has (the platform's list, spec item 12) — a satellite with
 * none needs its first one given.
 *
 * @extends BaseJob<array<int, int>>
 */
final class GetSatelliteAdminCountsJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    /** @return array<int, int> satellite id => active admins */
    public function handle(): array
    {
        $counts = [];
        $rows = RoleAssignment::query()
            ->where('church_id', $this->church->id)
            ->where('role', RoleEnum::Admin)
            ->where('scope_type', ScopeTypeEnum::Satellite)
            ->where('is_active', true)
            ->get(['scope_id']);
        foreach ($rows as $row) {
            $counts[$row->scope_id] = ($counts[$row->scope_id] ?? 0) + 1;
        }

        return $counts;
    }
}
