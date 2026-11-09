<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Make a login the platform admin, or take it back (spec item 12) — set at the terminal only. No Role is given or taken:
 * the platform admin opens every satellite as its admin while they are one.
 *
 * @extends BaseJob<void>
 */
final class UpdateUserPlatformAdminJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly bool $isPlatformAdmin,
    ) {}

    public function handle(): void
    {
        $this->user->forceFill(['is_platform_admin' => $this->isPlatformAdmin])->save();
    }
}
