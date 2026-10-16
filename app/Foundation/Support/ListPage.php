<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Foundation\DataObjects\PaginationData;
use Illuminate\Support\Collection;

/**
 * One page of a registration list (Roy 2026-10-16: lists are paged, 50 at a time). Cut from the already-filtered
 * list — fine at a church's size (hundreds); move the paging into the query if a church reaches thousands.
 *
 * @template TItem
 */
final readonly class ListPage
{
    /** @param list<TItem> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $lastPage,
        public int $total,
        public int $perPage,
    ) {}

    /**
     * @template T
     *
     * @param  Collection<int, T>  $all
     * @return self<T>
     */
    public static function of(Collection $all, int $requested, int $perPage = 50): self
    {
        $total = $all->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $requested), $lastPage);

        return new self(array_values($all->forPage($page, $perPage)->all()), $page, $lastPage, $total, $perPage);
    }

    /** Where this page sits, as a list page's `pagination` prop. */
    public function meta(): PaginationData
    {
        return PaginationData::fromListPage($this);
    }
}
