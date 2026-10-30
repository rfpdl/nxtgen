<?php

declare(strict_types=1);

namespace App\Domains\Card\DataObjects;

use App\Data\Models\PrintList;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A Print list still to print, on Cards' Approved tab (name cards 04): when and by whom it was made ("Sat 31 Oct
 * 10:31", the church's time), how many of its cards the viewer may see, and how many of those are still to print.
 * Built from GetOpenPrintListsJob's counts.
 */
#[TypeScript]
final class PrintListData extends Data
{
    public function __construct(
        public int $id,
        public string $made,
        public ?string $made_by,
        public int $cards,
        public int $to_print,
    ) {}

    public static function fromModel(PrintList $list, string $timezone): self
    {
        return new self(
            id: $list->id,
            made: $list->created_at->setTimezone($timezone)->format('D j M H:i'),
            made_by: $list->maker?->name,
            cards: (int) $list->cards_count,
            to_print: (int) $list->to_print_count,
        );
    }
}
