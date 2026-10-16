<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One place a signed-in person can go: their family page or the staff dashboard, in one church. */
#[TypeScript]
final class LandingOptionData extends Data
{
    public function __construct(
        public string $church_name,
        public string $label,
        public string $url,
    ) {}
}
