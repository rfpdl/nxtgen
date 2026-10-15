<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;

/**
 * A family by id, ONLY within this church — an id from a URL never reaches another church.
 *
 * @extends BaseJob<Family>
 */
final class GetFamilyByIdJob extends BaseJob
{
    public function __construct(
        private readonly int $organizationId,
        private readonly int $id,
    ) {}

    public function handle(): Family
    {
        return Family::query()
            ->with(['primaryGuardian.user', 'homeBranch', 'guardians', 'children.group', 'organization'])
            ->where('organization_id', $this->organizationId)
            ->findOrFail($this->id);
    }
}
