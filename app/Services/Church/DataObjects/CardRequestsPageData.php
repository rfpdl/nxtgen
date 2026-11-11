<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Card\DataObjects\CardRequestRowData;
use App\Domains\Card\DataObjects\PrintListData;
use App\Enums\CardListTabEnum;
use App\Foundation\DataObjects\PaginationData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Cards: one tab of Card requests, paged, and the print lists still to print (Church/Cards/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class CardRequestsPageData extends Data
{
    public function __construct(
        /** @var list<CardRequestRowData> */
        public array $requests,
        public PaginationData $pagination,
        public CardListTabEnum $tab,
        /** the child-name search, kept across tabs and pages (null = none) */
        public ?string $q,
        /** @var list<PrintListData> on Approved: the print lists still to print; empty elsewhere */
        public array $print_lists,
        /** @var array<string, list<array{value: string, label: string, color: string}>> the enums its forms read with useEnum() — page-wide, not a prop of the page */
        #[Hidden]
        public array $enums,
    ) {}
}
