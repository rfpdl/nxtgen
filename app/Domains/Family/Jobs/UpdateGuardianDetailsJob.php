<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Guardian;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Foundation\Jobs\BaseJob;

/**
 * Save a parent's own details. Never the email (plan §4.4) and never the login link (`user_id`).
 *
 * @extends BaseJob<Guardian>
 */
final class UpdateGuardianDetailsJob extends BaseJob
{
    public function __construct(
        private readonly Guardian $guardian,
        private readonly GuardianEditData $details,
    ) {}

    public function handle(): Guardian
    {
        $this->guardian->update([
            'first_name' => $this->details->firstName,
            'last_name' => $this->details->lastName,
            'relationship' => $this->details->relationship,
            'phone' => $this->details->phone,
            'ministries' => $this->details->ministries,
        ]);

        return $this->guardian;
    }
}
