<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * A Check-in by id, ONLY within this church, with its class, satellite and child — for an Undo.
 *
 * @extends BaseJob<CheckIn>
 */
final class GetCheckInJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): CheckIn
    {
        return CheckIn::query()
            ->where('church_id', $this->church->id)
            ->with(['session.schedule', 'session.satellite', 'child'])
            ->findOrFail($this->id);
    }
}
