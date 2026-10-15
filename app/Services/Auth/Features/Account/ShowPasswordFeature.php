<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Account;

use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserIsStaffJob;
use App\Services\Auth\Features\BaseAuthFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Backup password" for staff (plan D7) — for the morning the email is slow at church. Parents sign in by
 * email link only, so the page does not exist for them.
 *
 * @extends BaseAuthFeature<Response>
 */
final class ShowPasswordFeature extends BaseAuthFeature
{
    public function handle(): Response
    {
        $user = request()->user();
        abort_unless($user instanceof User && $this->run(new GetUserIsStaffJob(user: $user)), 404);

        return Inertia::render('Auth/Password', ['hasPassword' => $user->password !== null]);
    }
}
