<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Domains\Family\DataObjects\ChildListFiltersData;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The children list: children whose family's home satellite the viewer can see (`$satelliteIds` null = all), narrowed
 * by the filters, by nickname. A chosen satellite outside the viewer's yields nothing, never more.
 *
 * @extends BaseJob<Collection<int, Child>>
 */
final class GetChildrenJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly int $churchId,
        private readonly ?array $satelliteIds,
        private readonly ChildListFiltersData $filters = new ChildListFiltersData,
    ) {}

    /** @return Collection<int, Child> */
    public function handle(): Collection
    {
        $f = $this->filters;
        $like = $f->query === null ? null : '%'.str_replace(['%', '_'], ['\%', '\_'], trim($f->query)).'%';

        return Child::query()
            ->with(['family.homeSatellite', 'group'])
            ->where('church_id', $this->churchId)
            ->when($this->satelliteIds !== null, fn (Builder $q) => $q->whereHas('family', fn (Builder $fam) => $fam->whereIn('home_satellite_id', $this->satelliteIds ?? [])))
            ->when($f->satelliteId !== null, fn (Builder $q) => $q->whereHas('family', fn (Builder $fam) => $fam->where('home_satellite_id', $f->satelliteId)))
            // The child's own names, OR the family's parent (name, email, phone — Family::scopeMatchingContact), so
            // "Janice" finds Janice's children; a child's name does not drag in their siblings (review 4).
            ->when($like !== null, fn (Builder $q) => $q->where(fn (Builder $n) => $n
                ->where('nickname', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                ->orWhereHas('family', fn (Builder $fam) => $fam->matchingContact((string) $f->query))))
            ->when($f->status !== null, fn (Builder $q) => $q->where('status', $f->status))
            ->when($f->groupId !== null, fn (Builder $q) => $q->where('group_id', $f->groupId))
            ->orderBy('nickname')
            ->orderBy('id')
            ->get();
    }
}
