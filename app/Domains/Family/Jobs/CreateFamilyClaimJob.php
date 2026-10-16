<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\FamilyClaim;
use App\Data\Models\Guardian;
use App\Enums\FamilyClaimStatusEnum;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\EmailAddress;

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
            'church_id' => $this->family->church_id,
            'family_id' => $this->family->id,
            'guardian_id' => $this->guardian->id,
            'email_normalised' => EmailAddress::normalise($this->email),
            'revision' => 1,
            'status' => FamilyClaimStatusEnum::default(),
        ]);
    }
}
