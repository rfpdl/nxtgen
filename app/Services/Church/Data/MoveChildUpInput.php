<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One child left ticked on the Moving-Up page, with the Level the page showed — moved only if still there. */
#[TypeScript]
final class MoveChildUpInput extends Data
{
    public function __construct(
        public int $child_id,
        public int $level,
    ) {}
}
