<?php

declare(strict_types=1);

namespace App\Services\Console\Features\Platform;

use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserByEmailJob;
use App\Domains\User\Jobs\UpdateUserPlatformAdminJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Console\Features\BaseConsoleFeature;

/**
 * Make a login the platform admin, or take it back (spec item 12): at the terminal only, never from a screen. The
 * login must exist — nobody is made here. Null when no login has the email.
 *
 * @extends BaseConsoleFeature<User|null>
 */
#[UserStory(
    title: 'Set the platform admin',
    persona: 'someone at the terminal',
    happyPaths: ['story#188' => 'A login is made the platform admin, or taken back, from the terminal'],
    unhappyPaths: ['story#189' => 'An email no login has is refused; nobody is made'],
)]
final class SetPlatformAdminFeature extends BaseConsoleFeature
{
    public function __construct(
        private readonly string $email,
        private readonly bool $isPlatformAdmin,
    ) {}

    public function handle(): ?User
    {
        $user = $this->run(new GetUserByEmailJob(email: $this->email));
        if ($user !== null) {
            $this->run(new UpdateUserPlatformAdminJob(user: $user, isPlatformAdmin: $this->isPlatformAdmin));
        }

        return $user;
    }
}
