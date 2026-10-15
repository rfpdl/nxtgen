<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Change a login's email. Bumps credential_version, so every link sent to the old address stops working,
 * and clears the remember token (plan §4.3).
 *
 * @extends BaseJob<User>
 */
final class UpdateUserEmailJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly string $email,
    ) {}

    public function handle(): User
    {
        $this->user->forceFill([
            'email' => mb_strtolower(trim($this->email)),
            'email_verified_at' => null,
            'credential_version' => $this->user->credential_version + 1,
            'remember_token' => null,
        ])->save();

        return $this->user;
    }
}
