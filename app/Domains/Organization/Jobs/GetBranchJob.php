<?php

declare(strict_types=1);

namespace App\Domains\Organization\Jobs;

use App\Data\Models\Branch;
use App\Data\Models\Organization;
use App\Foundation\Jobs\BaseJob;

/**
 * A satellite by id, ONLY within this church — an id from a request body never reaches another church.
 *
 * @extends BaseJob<Branch>
 */
final class GetBranchJob extends BaseJob
{
    public function __construct(
        private readonly Organization $organization,
        private readonly int $id,
    ) {}

    public function handle(): Branch
    {
        return Branch::query()->where('organization_id', $this->organization->id)->findOrFail($this->id);
    }
}
