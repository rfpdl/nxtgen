<?php

declare(strict_types=1);

namespace App\Domains\Organization\Jobs;

use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Foundation\Jobs\BaseJob;

/**
 * A role by id, ONLY within this church — an id from the URL never reaches another church.
 *
 * @extends BaseJob<RoleAssignment>
 */
final class GetRoleAssignmentJob extends BaseJob
{
    public function __construct(
        private readonly Organization $organization,
        private readonly int $id,
    ) {}

    public function handle(): RoleAssignment
    {
        return RoleAssignment::query()->where('organization_id', $this->organization->id)->findOrFail($this->id);
    }
}
