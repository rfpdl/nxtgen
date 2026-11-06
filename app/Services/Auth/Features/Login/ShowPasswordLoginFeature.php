<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Login;

use App\Foundation\Attributes\UserStory;
use App\Services\Auth\Features\BaseAuthFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * /loginp (Roy, 2026-11-06): the backup password on its own page, already open — the same form and the same rule
 * (only someone who serves) as the one folded under the email link on /login.
 *
 * @extends BaseAuthFeature<Response>
 */
#[UserStory(
    title: 'Open the password sign-in',
    persona: 'staff',
    happyPaths: ['story#162' => 'The password sign-in has its own short address, open straight away'],
)]
final class ShowPasswordLoginFeature extends BaseAuthFeature
{
    public function handle(): Response
    {
        return Inertia::render('Auth/PasswordLogin');
    }
}
