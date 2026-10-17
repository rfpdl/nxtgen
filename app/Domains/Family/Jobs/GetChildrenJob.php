<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Domains\Family\DataObjects\ChildListFiltersData;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\ListPage;
use Illuminate\Database\Eloquent\Builder;

/**
 * The children list: children whose family's home satellite the viewer can see (`$satelliteIds` null = all), narrowed
 * by the filters, by nickname. A chosen satellite outside the viewer's yields nothing, never more. Paged in the
 * database, 50 at a time (ListPage); `$page` null = every filtered child.
 *
 * @extends BaseJob<ListPage<Child>>
 */
final class GetChildrenJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly ?array $satelliteIds,
        private readonly ChildListFiltersData $filters = new ChildListFiltersData,
        private readonly ?int $page = null,
    ) {}

    /** @return ListPage<Child> */
    public function handle(): ListPage
    {
        $f = $this->filters;
        $like = $f->query === null ? null : '%'.str_replace(['%', '_'], ['\%', '\_'], trim($f->query)).'%';

        $query = Child::query()
            ->with(['family.homeSatellite', 'family.primaryGuardian.user'])
            ->where('church_id', $this->church->id)
            ->when($this->satelliteIds !== null, fn (Builder $q) => $q->whereHas('family', fn (Builder $fam) => $fam->whereIn('home_satellite_id', $this->satelliteIds ?? [])))
            ->when($f->satelliteId !== null, fn (Builder $q) => $q->whereHas('family', fn (Builder $fam) => $fam->where('home_satellite_id', $f->satelliteId)))
            // The child's own names, OR the family's parent (name, email, phone — Family::scopeMatchingContact), so
            // "Janice" finds Janice's children; a child's name does not drag in their siblings (review 4).
            ->when($like !== null, fn (Builder $q) => $q->where(fn (Builder $n) => $n
                ->where('nickname', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                ->orWhereHas('family', fn (Builder $fam) => $fam->matchingContact((string) $f->query))))
            ->when($f->status !== null, fn (Builder $q) => $q->where('status', $f->status))
            ->when($f->level !== null, fn (Builder $q) => $q->where('level', $f->level))
            ->orderBy('nickname')
            ->orderBy('id');

        return $this->page === null ? ListPage::all($query) : ListPage::of($query, $this->page);
    }
}
