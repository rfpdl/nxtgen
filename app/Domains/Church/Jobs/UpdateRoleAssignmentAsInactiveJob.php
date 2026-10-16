<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\RoleAssignment;
use App\Foundation\Jobs\BaseJob;

/**
 * Remove a role. The row stays (for the record) with is_active off; GetAccessScopeJob reads active rows only,
 * so the person loses the access on their next page.
 *
 * @extends BaseJob<RoleAssignment>
 */
final class UpdateRoleAssignmentAsInactiveJob extends BaseJob
{
    public function __construct(private readonly RoleAssignment $assignment) {}

    public function handle(): RoleAssignment
    {
        $this->assignment->update(['is_active' => false]);

        return $this->assignment;
    }
}
