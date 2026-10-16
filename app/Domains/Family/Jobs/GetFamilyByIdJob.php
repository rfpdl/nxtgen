<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;

/**
 * A family by id, ONLY within this church — an id from a URL never reaches another church.
 *
 * @extends BaseJob<Family>
 */
final class GetFamilyByIdJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): Family
    {
        return Family::query()
            ->with(['primaryGuardian.user', 'homeSatellite', 'guardians', 'children', 'church'])
            ->where('church_id', $this->church->id)
            ->findOrFail($this->id);
    }
}
