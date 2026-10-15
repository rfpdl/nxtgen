<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

/** What the reviewed spreadsheet approves, plus anything approved that cannot be loaded as written. */
final readonly class RegistryApprovalData
{
    /**
     * @param  array<string, list<ApprovedChildRowData>>  $groups
     * @param  list<string>  $problems
     */
    public function __construct(
        public array $groups,
        public array $problems,
    ) {}
}
