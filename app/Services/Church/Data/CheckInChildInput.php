<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One ticked child at the door: the Level picked for a child who has none (ignored for a child who has one — the door
 * never changes a Level), and "make active again" for an inactive child (the door asks first).
 */
#[TypeScript]
final class CheckInChildInput extends Data
{
    public function __construct(
        public int $child_id,
        public ?int $level = null,
        public bool $make_active = false,
    ) {}
}
