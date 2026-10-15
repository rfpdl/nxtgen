<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Account;

use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserIsStaffJob;
use App\Domains\User\Jobs\UpdateUserPasswordJob;
use App\Services\Auth\Data\UpdatePasswordInput;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;

/** @extends BaseAuthFeature<RedirectResponse> */
final class UpdatePasswordFeature extends BaseAuthFeature
{
    public function handle(UpdatePasswordInput $input): RedirectResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User && $this->run(new GetUserIsStaffJob(user: $user)), 404);

        $this->run(new UpdateUserPasswordJob(user: $user, password: $input->password));

        return back()->with('success', 'Backup password saved. The email link still works as usual.');
    }
}
