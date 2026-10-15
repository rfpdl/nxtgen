<?php

declare(strict_types=1);

namespace App\Domains\Auth\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Support\Facades\Auth;

/**
 * Sign in and remember the device: a Lifeshaper's phone should stay signed in across Saturdays.
 * The session id is regenerated to prevent fixation.
 *
 * @extends BaseJob<User>
 */
final class SignInUserJob extends BaseJob
{
    public function __construct(private readonly User $user) {}

    public function handle(): User
    {
        Auth::login($this->user, remember: true);
        session()->regenerate();

        $this->user->forceFill(['last_login_at' => now()])->save();

        return $this->user;
    }
}
