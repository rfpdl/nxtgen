<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Foundation\Attributes\NonClientFacing;

/** A row the import will NOT load until a person fixes it in the sheet. */
#[NonClientFacing]
final readonly class QuarantinedRowData
{
    public function __construct(
        public int $rowNumber,
        public string $childName,
        public string $reason,
    ) {}
}
