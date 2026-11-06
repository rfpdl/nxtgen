<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\SatelliteLink;
use App\Foundation\Jobs\BaseJob;

/**
 * Move a Link one place up (`$up`) or down its satellite's poster: it swaps places with its neighbour there. At
 * either end there is no neighbour, so nothing moves (false). Two writes: the Feature runs it in a transaction.
 *
 * @extends BaseJob<bool>
 */
final class UpdateSatelliteLinkPositionJob extends BaseJob
{
    public function __construct(
        private readonly SatelliteLink $link,
        private readonly bool $up,
    ) {}

    public function handle(): bool
    {
        $neighbour = SatelliteLink::query()
            ->where('church_id', $this->link->church_id)
            ->where('satellite_id', $this->link->satellite_id)
            ->where('position', $this->up ? '<' : '>', $this->link->position)
            ->orderBy('position', $this->up ? 'desc' : 'asc')
            ->first();

        if ($neighbour === null) {
            return false;
        }

        $mine = $this->link->position;
        $this->link->forceFill(['position' => $neighbour->position])->save();
        $neighbour->forceFill(['position' => $mine])->save();

        return true;
    }
}
