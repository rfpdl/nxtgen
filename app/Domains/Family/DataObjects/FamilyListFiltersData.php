<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Enums\ChildStatusEnum;

/**
 * The registration list's filters (plan §6): search text, satellite, a child's status or Level, unconfirmed
 * email, open possible duplicate. All optional; they only ever NARROW the viewer's own scope.
 */
final readonly class FamilyListFiltersData
{
    public function __construct(
        public ?string $query = null,
        public ?int $satelliteId = null,
        public ?ChildStatusEnum $status = null,
        public ?int $level = null,
        public bool $unconfirmedOnly = false,
        public bool $duplicatesOnly = false,
    ) {}
}
