<?php

declare(strict_types=1);

use App\Foundation\Support\ListPage;
use Illuminate\Support\Collection;

/* The registration lists show 50 at a time (Roy 2026-10-16). */

it('cuts a list into pages of 50 and says where you are', function (): void {
    $page = ListPage::of(new Collection(range(1, 120)), requested: 3);

    expect($page->items)->toBe(range(101, 120))
        ->and([$page->page, $page->lastPage, $page->total, $page->perPage])->toBe([3, 3, 120, 50]);
});

it('treats a missing, zero or too-large page as the nearest real one', function (int $requested, int $expected): void {
    expect(ListPage::of(new Collection(range(1, 120)), requested: $requested)->page)->toBe($expected);
})->with([[0, 1], [-4, 1], [99, 3]]);

it('has one page even when the list is empty', function (): void {
    $page = ListPage::of(new Collection, requested: 1);

    expect([$page->items, $page->page, $page->lastPage, $page->total])->toBe([[], 1, 1, 0]);
});
