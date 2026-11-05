<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Enums\ChildStatusEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * How many of this church's Active children have no Level — Moving-Up passes them by, and its page says so.
 *
 * @extends BaseJob<int>
 */
final class GetChildrenWithoutLevelCountJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    public function handle(): int
    {
        return Child::query()
            ->where('church_id', $this->church->id)
            ->where('status', ChildStatusEnum::Active)
            ->whereNull('level')
            ->count();
    }
}
