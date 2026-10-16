<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\NonClientFacing;

/** The children list's filters. All optional; they only ever NARROW the viewer's own scope. */
#[NonClientFacing]
final readonly class ChildListFiltersData
{
    public function __construct(
        public ?string $query = null,
        public ?int $satellite_id = null,
        public ?ChildStatusEnum $status = null,
        public ?int $level = null,
    ) {}
}
