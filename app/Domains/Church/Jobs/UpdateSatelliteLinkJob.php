<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\SatelliteLink;
use App\Foundation\Jobs\BaseJob;

/**
 * Rename a Link or change its address; its place on the poster stays.
 *
 * @extends BaseJob<SatelliteLink>
 */
final class UpdateSatelliteLinkJob extends BaseJob
{
    public function __construct(
        private readonly SatelliteLink $link,
        private readonly string $label,
        private readonly string $url,
    ) {}

    public function handle(): SatelliteLink
    {
        $this->link->forceFill(['label' => $this->label, 'url' => $this->url])->save();

        return $this->link;
    }
}
