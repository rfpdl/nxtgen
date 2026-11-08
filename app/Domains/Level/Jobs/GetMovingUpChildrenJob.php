<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Enums\ChildStatusEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Who Moving-Up moves (DECISIONS 2026-11-06, per satellite 2026-11-08): this satellite's Active children with a Level — lowest
 * Level first, then by name — with their family and its satellite for the page. Inactive, Graduated and no-Level
 * children are not moved.
 *
 * @extends BaseJob<list<Child>>
 */
final class GetMovingUpChildrenJob extends BaseJob
{
    public function __construct(private readonly Satellite $satellite) {}

    /** @return list<Child> */
    public function handle(): array
    {
        return array_values(Child::query()
            ->where('church_id', $this->satellite->church_id)
            ->whereHas('family', fn ($f) => $f->where('home_satellite_id', $this->satellite->id))
            ->where('status', ChildStatusEnum::Active)
            ->whereNotNull('level')
            ->with('family.homeSatellite')
            ->orderBy('level')->orderBy('last_name')->orderBy('first_name')->orderBy('id')
            ->get()->all());
    }
}
