<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Domains\Family\DataObjects\ChildListFiltersData;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\LevelChart;
use App\Foundation\Support\ListPage;
use App\Foundation\Support\SearchWords;
use Illuminate\Database\Eloquent\Builder;

/**
 * The children list: children whose family's home satellite the viewer can see (`$satelliteIds`: the address
 * satellite, or none — DECISIONS 2026-11-08), narrowed by the filters, by nickname. Paged in the
 * database, 50 at a time (ListPage); `$page` null = every filtered child.
 *
 * @extends BaseJob<ListPage<Child>>
 */
final class GetChildrenJob extends BaseJob
{
    /** @param list<int> $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly array $satelliteIds,
        private readonly ChildListFiltersData $filters = new ChildListFiltersData,
        private readonly ?int $page = null,
    ) {}

    /** @return ListPage<Child> */
    public function handle(): ListPage
    {
        $f = $this->filters;

        $query = Child::query()
            ->with(['family.homeSatellite', 'family.primaryGuardian.user'])
            ->where('church_id', $this->church->id)
            ->whereHas('family', fn (Builder $fam) => $fam->whereIn('home_satellite_id', $this->satelliteIds))
            // Every word (SearchWords) on the child's own names OR their family's parent (Family::scopeWithParentMatching),
            // so "Janice" finds Janice's children and "Amara Reyes" the Amaras Reyes; a child's name does not drag in
            // their siblings (review 4). A phone-shaped part must be a parent's phone.
            ->when($f->query !== null, fn (Builder $q) => SearchWords::of((string) $f->query)->apply(
                $q,
                fn (Builder $one, string $word) => $one
                    ->where('nickname', 'like', SearchWords::like($word))
                    ->orWhere('first_name', 'like', SearchWords::like($word))
                    ->orWhere('last_name', 'like', SearchWords::like($word))
                    ->orWhereHas('family', fn (Builder $fam) => $fam->withParentMatching($word)),
                fn (Builder $one, string $digits) => $one->whereHas('family', fn (Builder $fam) => $fam->withParentPhone($digits)),
            ))
            ->when($f->status !== null, fn (Builder $q) => $q->where('status', $f->status))
            ->when($f->level === LevelChart::NONE, fn (Builder $q) => $q->whereNull('level'))
            ->when($f->level !== null && $f->level !== LevelChart::NONE, fn (Builder $q) => $q->where('level', $f->level))
            ->orderBy('nickname')
            ->orderBy('id');

        return $this->page === null ? ListPage::all($query) : ListPage::of($query, $this->page);
    }
}
