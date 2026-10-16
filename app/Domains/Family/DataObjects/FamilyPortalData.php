<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Family;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** What a parent sees about their OWN family (plan §3.3 FamilyPortalData). Never another family. */
#[TypeScript]
final class FamilyPortalData extends Data
{
    /**
     * @param  list<GuardianPortalData>  $guardians
     * @param  list<ChildPortalData>  $children
     */
    public function __construct(
        public string $homeSatellite,
        public ?string $area,
        public array $guardians,
        public array $children,
    ) {}

    public static function fromModel(Family $family): self
    {
        return new self(
            homeSatellite: $family->homeSatellite->name,
            area: $family->area_text,
            guardians: array_values($family->guardians->map(fn ($g) => GuardianPortalData::fromModel($g))->all()),
            children: array_values($family->children->sortBy('date_of_birth')->map(fn ($c) => ChildPortalData::fromModel($c))->all()),
        );
    }
}
