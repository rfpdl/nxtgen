<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Schedule;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Collection;

/**
 * Active roles in this church, with their person. `$satelliteIds` null = every role (church admin); a list =
 * only roles in those satellites and at their classes (a satellite admin sees their own satellite's people).
 *
 * @extends BaseJob<Collection<int, RoleAssignment>>
 */
final class GetStaffRolesJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly ?array $satelliteIds,
    ) {}

    /** @return Collection<int, RoleAssignment> */
    public function handle(): Collection
    {
        return RoleAssignment::query()
            ->with('user')
            ->where('church_id', $this->church->id)
            ->where('is_active', true)
            ->when($this->satelliteIds !== null, fn ($q) => $q->where(fn ($w) => $w
                ->where(fn ($at) => $at->where('scope_type', ScopeTypeEnum::Satellite)->whereIn('scope_id', $this->satelliteIds ?? []))
                // A class admin of a class at one of those satellites (TODO #9).
                ->orWhere(fn ($class) => $class->where('scope_type', ScopeTypeEnum::Schedule)->whereIn('scope_id', Schedule::query()
                    ->select('id')->where('church_id', $this->church->id)->whereIn('satellite_id', $this->satelliteIds ?? [])))))
            ->orderBy('id')
            ->get();
    }
}
