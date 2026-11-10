<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Active roles at `$satelliteIds` and at their classes, with their person — People shows the satellite in its address
 * (spec item 9).
 *
 * @extends BaseJob<Collection<int, RoleAssignment>>
 */
final class GetStaffRolesJob extends BaseJob
{
    /** @param list<int> $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly array $satelliteIds,
        /** Helpers for the day (spec item 18) are listed on their Session's day only: this one. */
        private readonly ?CarbonImmutable $today = null,
    ) {}

    /** @return Collection<int, RoleAssignment> */
    public function handle(): Collection
    {
        return RoleAssignment::query()
            ->with('user')
            ->where('church_id', $this->church->id)
            ->where('is_active', true)
            ->where(fn ($w) => $w
                ->where(fn ($at) => $at->where('scope_type', ScopeTypeEnum::Satellite)->whereIn('scope_id', $this->satelliteIds))
                // A class admin of a class at one of those satellites (TODO #9).
                ->orWhere(fn ($class) => $class->where('scope_type', ScopeTypeEnum::Schedule)->whereIn('scope_id', Schedule::query()
                    ->select('id')->where('church_id', $this->church->id)->whereIn('satellite_id', $this->satelliteIds)))
                // A helper for the day at one of their Sessions today (spec item 18).
                ->orWhere(fn ($helper) => $helper->where('scope_type', ScopeTypeEnum::Session)->whereIn('scope_id', Session::query()
                    ->select('id')->where('church_id', $this->church->id)->whereIn('satellite_id', $this->satelliteIds)
                    ->whereDate('date', ($this->today ?? $this->church->today())->toDateString()))))
            ->orderBy('id')
            ->get();
    }
}
