<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Foundation\Jobs\BaseJob;

/**
 * The composite foreign key refuses a guardian from another family.
 *
 * @extends BaseJob<Family>
 */
final class UpdateFamilyPrimaryGuardianJob extends BaseJob
{
    public function __construct(
        private readonly Family $family,
        private readonly Guardian $guardian,
    ) {}

    public function handle(): Family
    {
        $this->family->forceFill(['primary_guardian_id' => $this->guardian->id])->save();

        return $this->family;
    }
}
