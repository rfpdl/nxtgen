<?php

declare(strict_types=1);

namespace App\Services\Family\DataObjects;

use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\FamilyPortalData;
use App\Domains\Family\DataObjects\GuardianEditData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A parent's own family page (Family/Show) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class FamilyHomePageData extends Data
{
    public function __construct(
        public FamilyPortalData $family,
        public FamilyDetailsData $details,
        public GuardianEditData $me,
        /** @var list<GuardianEditData> */
        public array $other_guardians,
        /** @var list<SatelliteOptionData> */
        public array $satellites,
        /** @var array<string, list<array{value: string, label: string, color: string}>> the enums its forms read with useEnum() — page-wide, not a prop of the page */
        #[Hidden]
        public array $enums,
    ) {}
}
