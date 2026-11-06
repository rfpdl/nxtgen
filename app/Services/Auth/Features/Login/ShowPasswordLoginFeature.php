<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Login;

use App\Foundation\Attributes\UserStory;
use App\Services\Auth\Features\BaseAuthFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * /loginp (Roy, 2026-11-06): the backup password's only page — /login offers the emailed link alone. Only someone
 * who serves signs in with it (PasswordLoginFeature).
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
