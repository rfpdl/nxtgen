<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Enums\ChildStatusEnum;

/** The children list's filters. All optional; they only ever NARROW the viewer's own scope. */
final readonly class ChildListFiltersData
{
    public function __construct(
        public ?string $query = null,
        public ?int $satelliteId = null,
        public ?ChildStatusEnum $status = null,
        public ?int $groupId = null,
    ) {}
}
