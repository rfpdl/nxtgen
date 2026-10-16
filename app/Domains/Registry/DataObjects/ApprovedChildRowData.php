<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Foundation\Attributes\NonClientFacing;

/** One approved row read back from the reviewed spreadsheet, keyed by column name. */
#[NonClientFacing]
final readonly class ApprovedChildRowData
{
    /** @param array<string, string> $values */
    public function __construct(public array $values) {}

    public function get(string $column): string
    {
        return trim($this->values[$column] ?? '');
    }

    public function orNull(string $column): ?string
    {
        $v = $this->get($column);

        return $v === '' ? null : $v;
    }

    public function yes(string $column): bool
    {
        return str_starts_with(mb_strtolower($this->get($column)), 'y');
    }
}
