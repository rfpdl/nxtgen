<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

/** A family the import proposes: children grouped by parent email, for a human to check (plan §7). */
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
        public string $homeSatellite,
        public array $children,
        public array $suggestions,
    ) {}
}
