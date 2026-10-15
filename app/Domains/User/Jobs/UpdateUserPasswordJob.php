<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Set a staff member's backup password. Hashed by the model's `hashed` cast; never stored or logged plain.
 *
 * @extends BaseJob<User>
 */
final class UpdateUserPasswordJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        #[\SensitiveParameter] private readonly string $password,
    ) {}

    public function handle(): User
    {
        $this->user->forceFill(['password' => $this->password])->save();

        return $this->user;
    }
}
