<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Link a login to a guardian. The pending contact email moves to the user, so it is cleared here.
 * The database refuses a second family per user per church and a second login per family.
 *
 * @extends BaseJob<Guardian>
 */
final class UpdateGuardianUserJob extends BaseJob
{
    public function __construct(
        private readonly Guardian $guardian,
        private readonly User $user,
    ) {}

    public function handle(): Guardian
    {
        $this->guardian->forceFill(['user_id' => $this->user->id, 'email' => null])->save();

        return $this->guardian;
    }
}
