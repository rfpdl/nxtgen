<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Domains\Registry\Values\SheetCell;
use App\Foundation\Attributes\NonClientFacing;

/** One approved row read back from the reviewed spreadsheet, keyed by column name. */
#[NonClientFacing]
final readonly class ApprovedChildRowData
{
    /** @param array<string, string> $values */
    public function __construct(public array $values) {}

    public function get(string $column): string
    {
        return SheetCell::text($this->values[$column] ?? null);
    }

    public function orNull(string $column): ?string
    {
        return SheetCell::orNull($this->get($column));
    }

    public function yes(string $column): bool
    {
        return SheetCell::yes($this->get($column));
    }
}
