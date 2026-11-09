<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Foundation\Jobs\BaseJob;

/**
 * This church's satellites, in the order added — the platform's list (spec item 12).
 *
 * @extends BaseJob<list<Satellite>>
 */
final class GetSatellitesForChurchJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    /** @return list<Satellite> */
    public function handle(): array
    {
        return array_values(Satellite::query()->where('church_id', $this->church->id)->orderBy('id')->get()->all());
    }
}
