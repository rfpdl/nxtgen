<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Collection;

/**
 * Church-wide "is this family already registered?" (plan §7.2): every word on a parent's name, email or phone, or a
 * child's name/nickname (Family::scopeMatching — nothing left to match finds nothing). Church-wide on purpose — same
 * umbrella — and the caller only gets a lite projection.
 *
 * @extends BaseJob<Collection<int, Family>>
 */
final class SearchFamiliesJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly string $query,
    ) {}

    /** @return Collection<int, Family> */
    public function handle(): Collection
    {
        return Family::query()
            ->with(['primaryGuardian.user', 'homeSatellite', 'children'])
            ->where('church_id', $this->church->id)
            ->matching($this->query)
            ->limit(20)
            ->get();
    }
}
