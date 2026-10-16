<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\EmailAddress;
use Illuminate\Support\Facades\Hash;

/**
 * The login whose email and password match, or null. A password is checked even when no such login exists, so
 * the time taken does not reveal whether an email is registered.
 *
 * @extends BaseJob<User|null>
 */
final class GetUserByCredentialsJob extends BaseJob
{
    public function __construct(
        private readonly string $email,
        #[\SensitiveParameter] private readonly string $password,
    ) {}

    public function handle(): ?User
    {
        $user = User::query()->where('email', EmailAddress::normalise($this->email))->first();
        // A login with no password (parents, most staff) is checked against a throwaway hash too: same time taken.
        $hash = $user->password ?? Hash::make('no-such-login');
        $matches = Hash::check($this->password, $hash);

        return $matches && $user !== null && $user->password !== null ? $user : null;
    }
}
