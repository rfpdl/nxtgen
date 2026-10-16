<?php

declare(strict_types=1);

namespace App\Foundation\DataObjects;

use App\Foundation\Support\ListPage;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Where a paged list is: page 2 of 3, 120 in all, 50 a page. The one pagination shape every list page reads. */
#[TypeScript]
final class PaginationData extends Data
{
    public function __construct(
        public int $page,
        public int $last_page,
        public int $total,
        public int $per_page,
    ) {}

    /** @param ListPage<mixed> $page */
    public static function fromListPage(ListPage $page): self
    {
        return new self(page: $page->page, last_page: $page->lastPage, total: $page->total, per_page: $page->perPage);
    }
}
