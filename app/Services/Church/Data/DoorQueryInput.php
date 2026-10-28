<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The door's address, on both tabs: `?class=` (one of today's classes — none = the first) and, on Who's in, `?squad=`
 * (one Squad — none = All).
 */
#[TypeScript]
final class DoorQueryInput extends Data
{
    public function __construct(
        public ?int $class = null,
        public ?int $squad = null,
    ) {}
}
