<?php

declare(strict_types=1);

namespace App\Operations\Church;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Domains\Church\Jobs\GetSatelliteAdminCountJob;
use App\Domains\Church\Jobs\GetSatelliteJob;
use App\Domains\Church\Jobs\UpdateRoleAssignmentAsInactiveJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Switch a Role off, kept for the record (People, spec item 9; the platform's People, spec item 12) — never the last
 * admin of a satellite: counted under the satellite's lock, so two admins removing each other at once cannot both go.
 * The caller decided who may remove it. Answers why it was refused, or null when it was removed.
 *
 * @extends Operation<string|null>
 */
final class RemoveRoleOperation extends Operation
{
    public function __construct(
        private readonly Church $church,
        private readonly RoleAssignment $assignment,
    ) {}

    public function handle(): ?string
    {
        $satellite = $this->assignment->role === RoleEnum::Admin && $this->assignment->scope_type === ScopeTypeEnum::Satellite
            ? $this->run(new GetSatelliteJob(church: $this->church, id: $this->assignment->scope_id))
            : null;

        $removed = DB::transaction(function () use ($satellite): bool {
            if ($satellite !== null && $this->run(new GetSatelliteAdminCountJob(satellite: $satellite)) <= 1) {
                return false;
            }
            $this->run(new UpdateRoleAssignmentAsInactiveJob(assignment: $this->assignment));

            return true;
        });

        return $removed ? null : "This is the only admin of {$satellite?->name}. Add another admin there first.";
    }
}
