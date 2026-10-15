<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\FamilyClaim;
use App\Enums\FamilyClaimStatusEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<FamilyClaim> */
final class UpdateFamilyClaimStatusJob extends BaseJob
{
    public function __construct(
        private readonly FamilyClaim $claim,
        private readonly FamilyClaimStatusEnum $status,
    ) {}

    public function handle(): FamilyClaim
    {
        $this->claim->forceFill([
            'status' => $this->status,
            'resolved_at' => $this->status === FamilyClaimStatusEnum::Pending ? null : now(),
        ])->save();

        return $this->claim;
    }
}
