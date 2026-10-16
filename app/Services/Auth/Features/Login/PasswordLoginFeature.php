<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Login;

use App\Domains\Auth\Jobs\SignInUserJob;
use App\Domains\User\Jobs\GetUserByCredentialsJob;
use App\Domains\User\Jobs\GetUserIsStaffJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Auth\Data\PasswordLoginInput;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Sign in with a backup password (plan D7): only someone who serves somewhere RIGHT NOW. A parent — even one who
 * once served and still has a password — signs in by email link. One answer for every failure.
 *
 * @extends BaseAuthFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Sign in with a password',
    persona: 'staff',
    happyPaths: ['story#16' => 'Someone who serves signs in with their backup password'],
    unhappyPaths: [
        'story#17' => 'A parent, or someone who no longer serves, cannot sign in with a password',
        'story#18' => 'A wrong password and an unknown email get the same answer, and guessing is limited',
    ],
)]
final class PasswordLoginFeature extends BaseAuthFeature
{
    public function handle(PasswordLoginInput $input): RedirectResponse
    {
        $user = $this->run(new GetUserByCredentialsJob(email: $input->email, password: $input->password));

        if ($user === null || ! $this->run(new GetUserIsStaffJob(user: $user))) {
            throw ValidationException::withMessages([
                'email' => 'That email and password do not match. You can always use the email link instead.',
            ]);
        }

        $this->run(new SignInUserJob(user: $user));

        return redirect('/home');
    }
}
