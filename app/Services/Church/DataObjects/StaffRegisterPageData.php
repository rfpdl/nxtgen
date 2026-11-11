<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Family\DataObjects\FamilySearchResultData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Register a family, in Serve: the search first, then the form (Church/Families/Register) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class StaffRegisterPageData extends Data
{
    public function __construct(
        public string $query,
        /** @var list<FamilySearchResultData> */
        public array $results,
        /** @var list<SatelliteOptionData> */
        public array $satellites,
        /** @var array<string, list<array{value: string, label: string, color: string}>> the enums its forms read with useEnum() — page-wide, not a prop of the page */
        #[Hidden]
        public array $enums,
    ) {}
}
