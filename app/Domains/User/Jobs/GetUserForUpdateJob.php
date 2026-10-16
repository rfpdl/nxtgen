<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Re-read a login and LOCK its row for the rest of the caller's transaction, so redeeming a link and
 * correcting the email can never interleave. The transaction belongs to the Feature/Operation.
 *
 * @extends BaseJob<User>
 */
final class GetUserForUpdateJob extends BaseJob
{
    public function __construct(private readonly User $user) {}

    public function handle(): User
    {
        return User::query()->lockForUpdate()->findOrFail($this->user->id);
    }
}
