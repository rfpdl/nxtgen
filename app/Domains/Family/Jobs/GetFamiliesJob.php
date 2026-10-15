<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Families of one church, limited to the satellites the viewer may see (null = all). Newest first.
 *
 * @extends BaseJob<Collection<int, Family>>
 */
final class GetFamiliesJob extends BaseJob
{
    /** @param list<int>|null $branchIds */
    public function __construct(
        private readonly int $organizationId,
        private readonly ?array $branchIds,
        private readonly bool $unconfirmedOnly = false,
    ) {}

    /** @return Collection<int, Family> */
    public function handle(): Collection
    {
        return Family::query()
            ->with(['primaryGuardian.user', 'homeBranch', 'children.group'])
            ->where('organization_id', $this->organizationId)
            ->when($this->branchIds !== null, fn (Builder $q) => $q->whereIn('home_branch_id', $this->branchIds ?? []))
            ->when($this->unconfirmedOnly, fn (Builder $q) => $q->whereHas('primaryGuardian', fn (Builder $g) => $g
                ->whereNull('user_id')
                ->orWhereHas('user', fn (Builder $u) => $u->whereNull('email_verified_at'))))
            ->latest('id')
            ->get();
    }
}
