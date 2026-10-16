<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Foundation\Jobs\BaseJob;

/**
 * An adult on a family. Linked to a login (`user`) when the email is new or confirmed; otherwise the email
 * is kept on the guardian until the owner accepts the claim (plan §4.2).
 *
 * @extends BaseJob<Guardian>
 */
final class CreateGuardianJob extends BaseJob
{
    public function __construct(
        private readonly Family $family,
        private readonly GuardianDetailsData $details,
        private readonly ?User $user,
    ) {}

    public function handle(): Guardian
    {
        $phone = trim((string) $this->details->phone);

        return Guardian::query()->create([
            'church_id' => $this->family->church_id,
            'family_id' => $this->family->id,
            'user_id' => $this->user?->id,
            'relationship' => $this->details->relationship,
            'first_name' => trim($this->details->first_name),
            'last_name' => trim($this->details->last_name),
            'phone' => $phone === '' ? null : $phone,
            'email' => $this->user === null ? mb_strtolower(trim($this->details->email)) : null,
            'ministries' => $this->details->ministries,
            'can_pick_up' => true,
        ]);
    }
}
