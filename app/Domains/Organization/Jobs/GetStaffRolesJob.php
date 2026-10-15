<?php

declare(strict_types=1);

namespace App\Domains\Organization\Jobs;

use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Collection;

/**
 * Active roles in this church, with their person. `$branchIds` null = every role (church admin); a list =
 * only roles in those satellites (a satellite admin sees their own satellite's people).
 *
 * @extends BaseJob<Collection<int, RoleAssignment>>
 */
final class GetStaffRolesJob extends BaseJob
{
    /** @param list<int>|null $branchIds */
    public function __construct(
        private readonly Organization $organization,
        private readonly ?array $branchIds,
    ) {}

    /** @return Collection<int, RoleAssignment> */
    public function handle(): Collection
    {
        return RoleAssignment::query()
            ->with('user')
            ->where('organization_id', $this->organization->id)
            ->where('is_active', true)
            ->when($this->branchIds !== null, fn ($q) => $q
                ->where('scope_type', ScopeTypeEnum::Branch)
                ->whereIn('scope_id', $this->branchIds ?? []))
            ->orderBy('id')
            ->get();
    }
}
