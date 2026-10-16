<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Domains\Family\DataObjects\ChildListFiltersData;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The children list: children whose family's home satellite the viewer can see (`$branchIds` null = all), narrowed
 * by the filters, by nickname. A chosen satellite outside the viewer's yields nothing, never more.
 *
 * @extends BaseJob<Collection<int, Child>>
 */
final class GetChildrenJob extends BaseJob
{
    /** @param list<int>|null $branchIds */
    public function __construct(
        private readonly int $organizationId,
        private readonly ?array $branchIds,
        private readonly ChildListFiltersData $filters = new ChildListFiltersData,
    ) {}

    /** @return Collection<int, Child> */
    public function handle(): Collection
    {
        $f = $this->filters;
        $like = $f->query === null ? null : '%'.str_replace(['%', '_'], ['\%', '\_'], trim($f->query)).'%';

        return Child::query()
            ->with(['family.homeBranch', 'group'])
            ->where('organization_id', $this->organizationId)
            ->when($this->branchIds !== null, fn (Builder $q) => $q->whereHas('family', fn (Builder $fam) => $fam->whereIn('home_branch_id', $this->branchIds ?? [])))
            ->when($f->branchId !== null, fn (Builder $q) => $q->whereHas('family', fn (Builder $fam) => $fam->where('home_branch_id', $f->branchId)))
            ->when($like !== null, fn (Builder $q) => $q->where(fn (Builder $n) => $n
                ->where('nickname', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like)))
            ->when($f->status !== null, fn (Builder $q) => $q->where('status', $f->status))
            ->when($f->groupId !== null, fn (Builder $q) => $q->where('group_id', $f->groupId))
            ->orderBy('nickname')
            ->orderBy('id')
            ->get();
    }
}
