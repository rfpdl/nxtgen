<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * The satellites a person may open, in the order added (Satellite::openTo) — "Pick your satellite" and the bar's switch
 * (spec items 11, 13).
 *
 * @extends BaseJob<list<Satellite>>
 */
final class GetOpenSatellitesJob extends BaseJob
{
    public function __construct(private readonly User $user) {}

    /** @return list<Satellite> */
    public function handle(): array
    {
        return array_values(Satellite::query()->openTo($this->user)->orderBy('id')->get()->all());
    }
}
