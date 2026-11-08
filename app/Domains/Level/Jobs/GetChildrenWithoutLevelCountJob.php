<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Enums\ChildStatusEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * How many of this satellite's Active children have no Level — Moving-Up passes them by, and its page says so.
 *
 * @extends BaseJob<int>
 */
final class GetChildrenWithoutLevelCountJob extends BaseJob
{
    public function __construct(private readonly Satellite $satellite) {}

    public function handle(): int
    {
        return Child::query()
            ->where('church_id', $this->satellite->church_id)
            ->whereHas('family', fn ($f) => $f->where('home_satellite_id', $this->satellite->id))
            ->where('status', ChildStatusEnum::Active)
            ->whereNull('level')
            ->count();
    }
}
