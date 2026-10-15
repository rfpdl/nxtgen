<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * A new login with no password and an unconfirmed email.
 *
 * @extends BaseJob<User>
 */
final class CreateUserJob extends BaseJob
{
    public function __construct(
        private readonly string $name,
        private readonly string $email,
    ) {}

    public function handle(): User
    {
        return User::query()->create([
            'name' => trim($this->name),
            'email' => mb_strtolower(trim($this->email)),
        ]);
    }
}
