<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\FamilyClaim;
use App\Data\Models\Organization;
use App\Enums\FamilyClaimStatusEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<FamilyClaim|null> */
final class GetPendingFamilyClaimJob extends BaseJob
{
    public function __construct(
        private readonly Organization $organization,
        private readonly string $email,
    ) {}

    public function handle(): ?FamilyClaim
    {
        return FamilyClaim::query()
            ->where('organization_id', $this->organization->id)
            ->where('email_normalised', mb_strtolower(trim($this->email)))
            ->where('status', FamilyClaimStatusEnum::Pending)
            ->first();
    }
}
