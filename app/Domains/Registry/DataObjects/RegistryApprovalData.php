<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Foundation\Attributes\NonClientFacing;

/** What the reviewed spreadsheet approves, plus anything approved that cannot be loaded as written. */
#[NonClientFacing]
final readonly class RegistryApprovalData
{
    /**
     * @param  array<string, list<ApprovedChildRowData>>  $rows_by_family
     * @param  list<string>  $problems
     */
    public function __construct(
        public array $rows_by_family,
        public array $problems,
    ) {}
}
