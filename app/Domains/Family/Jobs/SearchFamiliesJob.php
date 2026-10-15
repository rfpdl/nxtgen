<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Collection;

/**
 * Church-wide "is this family already registered?" (plan §7.2): parent name, email, phone digits, or a
 * child's name/nickname. Org-wide on purpose — same umbrella — and the caller only gets a lite projection.
 *
 * @extends BaseJob<Collection<int, Family>>
 */
final class SearchFamiliesJob extends BaseJob
{
    public function __construct(
        private readonly int $organizationId,
        private readonly string $query,
    ) {}

    /** @return Collection<int, Family> */
    public function handle(): Collection
    {
        if (mb_strlen(trim($this->query)) < 2) {
            return new Collection;
        }

        return Family::query()
            ->with(['primaryGuardian.user', 'homeBranch', 'children'])
            ->where('organization_id', $this->organizationId)
            ->matching($this->query)
            ->limit(20)
            ->get();
    }
}
