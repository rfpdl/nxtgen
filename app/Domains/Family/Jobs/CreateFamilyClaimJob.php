<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\FamilyClaim;
use App\Data\Models\Guardian;
use App\Enums\FamilyClaimStatusEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<FamilyClaim> */
final class CreateFamilyClaimJob extends BaseJob
{
    public function __construct(
        private readonly Family $family,
        private readonly Guardian $guardian,
        private readonly string $email,
    ) {}

    public function handle(): FamilyClaim
    {
        return FamilyClaim::query()->create([
            'organization_id' => $this->family->organization_id,
            'family_id' => $this->family->id,
            'guardian_id' => $this->guardian->id,
            'email_normalised' => mb_strtolower(trim($this->email)),
            'revision' => 1,
            'status' => FamilyClaimStatusEnum::Pending,
        ]);
    }
}
