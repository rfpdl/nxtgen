<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Foundation\Attributes\NonClientFacing;

/** What proposing the registry did (step 1, plan §7): the rows read, and where they went — for the operator. */
#[NonClientFacing]
final readonly class RegistryProposalSummaryData
{
    public function __construct(
        public int $rows,
        public int $families,
        public int $droppedDuplicates,
        public int $quarantined,
        public string $path,
    ) {}
}
