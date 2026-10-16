<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Foundation\Attributes\NonClientFacing;

/** A family the import proposes: children grouped by parent email, for a human to check (plan §7). */
#[NonClientFacing]
final readonly class ProposedFamilyData
{
    /**
     * @param  list<RegistryRowData>  $children
     * @param  list<string>  $suggestions
     */
    public function __construct(
        public string $key,
        public string $email,
        public RegistryRowData $primary,
        public string $home_satellite,
        public array $children,
        public array $suggestions,
    ) {}
}
