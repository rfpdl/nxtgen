<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Satellite;
use App\Foundation\Jobs\BaseJob;

/**
 * Re-read the satellite and LOCK its row for the rest of the caller's transaction: its Links are added, moved and
 * removed one at a time, so a second admin waits and then sees what the first did — never a seventh Link, never two
 * in one place (Astra). The transaction is the caller's.
 *
 * @extends BaseJob<Satellite>
 */
final class GetSatelliteForUpdateJob extends BaseJob
{
    public function __construct(private readonly Satellite $satellite) {}

    public function handle(): Satellite
    {
        return Satellite::query()
            ->where('church_id', $this->satellite->church_id)
            ->whereKey($this->satellite->id)
            ->lockForUpdate()
            ->sole();
    }
}
