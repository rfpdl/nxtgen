<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Church;
use App\Data\Models\FamilyClaim;
use App\Enums\FamilyClaimStatusEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<FamilyClaim|null> */
final class GetPendingFamilyClaimJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly string $email,
    ) {}

    public function handle(): ?FamilyClaim
    {
        return FamilyClaim::query()
            ->where('church_id', $this->church->id)
            ->where('email_normalised', mb_strtolower(trim($this->email)))
            ->where('status', FamilyClaimStatusEnum::Pending)
            ->first();
    }
}
