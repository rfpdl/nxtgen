<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Foundation\Support\ListPage;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/*
 * The registration lists show 50 at a time (Roy 2026-10-16). The page is cut IN THE DATABASE (TODO #24): one count,
 * then only that page's rows — so a church with thousands of families never loads them all to show fifty.
 */

/** @return list<int> the ids of `$count` new churches, oldest first */
function churchesMade(int $count): array
{
    return array_values(Church::factory()->count($count)->create()->map(fn (Church $c): int => $c->id)->all());
}

it('cuts a query into pages of 50 and says where you are', function (): void {
    $ids = churchesMade(120);

    $page = ListPage::of(Church::query()->orderBy('id'), requested: 3);

    expect(array_map(fn (Church $c): int => $c->id, $page->items))->toBe(array_slice($ids, 100))
        ->and([$page->page, $page->lastPage, $page->total, $page->perPage])->toBe([3, 3, 120, 50]);
});

it('asks the database for one page only — a count, then fifty rows', function (): void {
    churchesMade(120);
    $selects = [];
    DB::listen(function (QueryExecuted $query) use (&$selects): void {
        $selects[] = strtolower($query->sql);
    });

    ListPage::of(Church::query()->orderBy('id'), requested: 2);

    expect($selects)->toHaveCount(2)
        ->and($selects[0])->toContain('count(')
        ->and($selects[1])->toContain('limit 50')->toContain('offset 50');
});

it('treats a missing, zero or too-large page as the nearest real one', function (int $requested, int $expected): void {
    churchesMade(120);

    expect(ListPage::of(Church::query()->orderBy('id'), requested: $requested)->page)->toBe($expected);
})->with([[0, 1], [-4, 1], [99, 3]]);

it('has one page even when the list is empty', function (): void {
    $page = ListPage::of(Church::query(), requested: 1);

    expect([$page->items, $page->page, $page->lastPage, $page->total])->toBe([[], 1, 1, 0]);
});

it('hands back every row as one page when no page is asked for — the Excel download keeps them all', function (): void {
    $ids = churchesMade(120);

    $all = ListPage::all(Church::query()->orderBy('id'));

    expect(array_map(fn (Church $c): int => $c->id, $all->items))->toBe($ids)
        ->and([$all->page, $all->lastPage, $all->total])->toBe([1, 1, 120]);
});

it('describes itself to the page as the one pagination shape, in snake_case', function (): void {
    churchesMade(120);

    $meta = ListPage::of(Church::query(), requested: 2)->meta();

    expect([$meta->page, $meta->last_page, $meta->total, $meta->per_page])->toBe([2, 3, 120, 50]);
});
