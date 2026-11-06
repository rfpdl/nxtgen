<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\SatelliteLink;
use App\Foundation\Jobs\BaseJob;

/**
 * Remove a Link from its satellite's poster. Nothing else refers to one, so it is deleted, not kept.
 *
 * @extends BaseJob<bool>
 */
final class DeleteSatelliteLinkJob extends BaseJob
{
    public function __construct(private readonly SatelliteLink $link) {}

    public function handle(): bool
    {
        return $this->link->delete() === true;
    }
}
