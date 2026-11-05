<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Enums\ChildStatusEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Who Moving-Up moves (DECISIONS 2026-11-06): this church's Active children with a Level, every satellite — lowest
 * Level first, then by name — with their family and its satellite for the page. Inactive, Graduated and no-Level
 * children are not moved.
 *
 * @extends BaseJob<list<Child>>
 */
final class GetMovingUpChildrenJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    /** @return list<Child> */
    public function handle(): array
    {
        return array_values(Child::query()
            ->where('church_id', $this->church->id)
            ->where('status', ChildStatusEnum::Active)
            ->whereNotNull('level')
            ->with('family.homeSatellite')
            ->orderBy('level')->orderBy('last_name')->orderBy('first_name')->orderBy('id')
            ->get()->all());
    }
}
