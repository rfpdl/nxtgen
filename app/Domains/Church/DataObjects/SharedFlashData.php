<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The one-time message a redirect left, on every page (SharedPropsData): read from the session when asked for. */
#[TypeScript]
final class SharedFlashData extends Data
{
    public function __construct(
        #[LiteralTypeScriptType('string | null')]
        public Lazy|string|null $success,
        #[LiteralTypeScriptType('string | null')]
        public Lazy|string|null $error,
    ) {}
}
