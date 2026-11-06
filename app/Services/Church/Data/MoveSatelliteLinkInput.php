<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Move a Link one place up or down its satellite's poster. */
#[TypeScript]
final class MoveSatelliteLinkInput extends Data
{
    public function __construct(
        public string $direction,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return ['direction' => ['required', 'in:up,down']];
    }
}
