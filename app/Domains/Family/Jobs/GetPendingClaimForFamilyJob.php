<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\FamilyClaim;
use App\Enums\FamilyClaimStatusEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<FamilyClaim|null> */
final class GetPendingClaimForFamilyJob extends BaseJob
{
    public function __construct(private readonly Family $family) {}

    public function handle(): ?FamilyClaim
    {
        return FamilyClaim::query()
            ->where('family_id', $this->family->id)
            ->where('status', FamilyClaimStatusEnum::Pending)
            ->first();
    }
}
