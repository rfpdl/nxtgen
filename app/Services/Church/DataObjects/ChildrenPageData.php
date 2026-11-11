<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Family\DataObjects\ChildListRowData;
use App\Domains\Family\DataObjects\ListFiltersData;
use App\Domains\Level\DataObjects\LevelOptionData;
use App\Foundation\DataObjects\PaginationData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Children: the satellite's children, paged and filtered (Church/Children/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class ChildrenPageData extends Data
{
    public function __construct(
        /** @var list<ChildListRowData> */
        public array $children,
        public PaginationData $pagination,
        public ListFiltersData $filters,
        /** @var array{levels: list<LevelOptionData>} the Level filter's choices, "No Level" last */
        public array $options,
        /** @var array<string, list<array{value: string, label: string, color: string}>> the enums its forms read with useEnum() — page-wide, not a prop of the page */
        #[Hidden]
        public array $enums,
    ) {}
}
