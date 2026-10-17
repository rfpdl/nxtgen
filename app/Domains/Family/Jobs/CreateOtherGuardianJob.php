<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Foundation\Jobs\BaseJob;

/**
 * One of a family's OTHER guardians (a father, a grandparent …): never a login and never an email — the family's one
 * login is the primary guardian's (DECISIONS 2026-10-17, after first look). The primary is CreateGuardianJob's.
 *
 * @extends BaseJob<Guardian>
 */
final class CreateOtherGuardianJob extends BaseJob
{
    public function __construct(
        private readonly Family $family,
        private readonly GuardianEditData $details,
    ) {}

    public function handle(): Guardian
    {
        return Guardian::query()->create([
            'church_id' => $this->family->church_id,
            'family_id' => $this->family->id,
            'user_id' => null,
            'email' => null,
            'relationship' => $this->details->relationship,
            'first_name' => $this->details->first_name,
            'last_name' => $this->details->last_name,
            'phone' => $this->details->phone,
            'ministries' => $this->details->ministries,
            'can_pick_up' => true,
        ]);
    }
}
