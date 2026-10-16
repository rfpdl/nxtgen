<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
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
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): RoleAssignment
    {
        return RoleAssignment::query()->where('church_id', $this->church->id)->findOrFail($this->id);
    }
}
