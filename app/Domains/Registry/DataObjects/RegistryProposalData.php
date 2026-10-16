<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Foundation\Attributes\NonClientFacing;

#[NonClientFacing]
final readonly class RegistryProposalData
{
    /**
     * @param  list<ProposedFamilyData>  $families
     * @param  list<QuarantinedRowData>  $quarantined
     */
    public function __construct(
        public array $families,
        public int $dropped_duplicates,
        public array $quarantined,
    ) {}
}
