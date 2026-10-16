<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Foundation\DataObjects\PaginationData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One page of a registration list (Roy 2026-10-16: lists are paged, 50 at a time), cut in the database (TODO #24):
 * one count, then only that page's rows — and only theirs are eager-loaded. A page past either end is the nearest
 * real one. `all()` is the whole filtered list as one page, for the Excel download.
 *
 * @template TItem of Model
 */
final readonly class ListPage
{
    public const int PER_PAGE = 50;

    /** @param list<TItem> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $lastPage,
        public int $total,
        public int $perPage,
    ) {}

    /**
     * @template T of Model
     *
     * @param  Builder<T>  $query  filtered and ordered; its eager loads run for this page only
     * @return self<T>
     */
    public static function of(Builder $query, int $requested, int $perPage = self::PER_PAGE): self
    {
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $requested), $lastPage);
        $items = $total === 0 ? [] : array_values($query->forPage($page, $perPage)->get()->all());

        return new self($items, $page, $lastPage, $total, $perPage);
    }

    /**
     * Every row the query finds, as one page.
     *
     * @template T of Model
     *
     * @param  Builder<T>  $query
     * @return self<T>
     */
    public static function all(Builder $query): self
    {
        $items = array_values($query->get()->all());

        return new self($items, 1, 1, count($items), max(1, count($items)));
    }

    /** Where this page sits, as a list page's `pagination` prop. */
    public function meta(): PaginationData
    {
        return PaginationData::fromListPage($this);
    }
}
