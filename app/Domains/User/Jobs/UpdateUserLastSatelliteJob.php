<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Keep the satellite a person opened last on their account (spec item 11; DECISIONS 2026-11-08: it follows them to
 * every device) — where they land after signing in next time.
 *
 * @extends BaseJob<void>
 */
final class UpdateUserLastSatelliteJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly Satellite $satellite,
    ) {}

    public function handle(): void
    {
        $this->user->forceFill(['last_satellite_id' => $this->satellite->id])->save();
    }
}
