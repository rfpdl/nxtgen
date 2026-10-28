<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A tap on Who's in: mark the child present (true) or unmark them (false). */
#[TypeScript]
final class MarkPresentInput extends Data
{
    public function __construct(public bool $present) {}
}
