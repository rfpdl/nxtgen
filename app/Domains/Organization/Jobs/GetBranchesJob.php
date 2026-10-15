<?php

declare(strict_types=1);

namespace App\Domains\Organization\Jobs;

use App\Data\Models\Branch;
use App\Data\Models\Organization;
use App\Domains\Organization\DataObjects\BranchOptionData;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<list<BranchOptionData>> */
final class GetBranchesJob extends BaseJob
{
    public function __construct(private readonly Organization $organization) {}

    /** @return list<BranchOptionData> */
    public function handle(): array
    {
        return array_values(Branch::query()
            ->where('organization_id', $this->organization->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $b): BranchOptionData => BranchOptionData::fromModel($b))
            ->all());
    }
}
