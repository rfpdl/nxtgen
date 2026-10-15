<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<User|null> */
final class GetUserByEmailJob extends BaseJob
{
    public function __construct(private readonly string $email) {}

    public function handle(): ?User
    {
        return User::query()->where('email', mb_strtolower(trim($this->email)))->first();
    }
}
