<?php

declare(strict_types=1);

namespace App\Services\Public\DataObjects;

use App\Domains\Church\DataObjects\SatelliteOptionData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A parent registering from the poster (Public/Register) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class RegisterPageData extends Data
{
    public function __construct(
        /** @var list<SatelliteOptionData> */
        public array $satellites,
        /** @var array<string, list<array{value: string, label: string, color: string}>> the enums its forms read with useEnum() — page-wide, not a prop of the page */
        #[Hidden]
        public array $enums,
    ) {}
}
