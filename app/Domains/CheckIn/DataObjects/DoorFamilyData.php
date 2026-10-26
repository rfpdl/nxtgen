<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Child;
use App\Data\Models\Family;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A family found by the door's church-wide search: "Cruz family", the parent's name and home satellite, and its
 * children (DoorChildData — no photo, no phone, an allergy flag only). Needs primaryGuardian, homeSatellite and
 * children loaded (SearchFamiliesJob does).
 */
#[TypeScript]
final class DoorFamilyData extends Data
{
    /** @param list<DoorChildData> $children */
    public function __construct(
        public int $id,
        public string $name,
        public string $primary_name,
        public string $satellite,
        public array $children,
    ) {}

    /**
     * @param  array<int, int>  $checkedIn  child id => check-in id, for the chosen class today
     * @param  array<int, int|null>  $suggestedLevels  child id => the Level their age points to (children with none)
     */
    public static function fromModel(Family $family, array $checkedIn, array $suggestedLevels): self
    {
        $surname = $family->primaryGuardian?->last_name;

        return new self(
            id: $family->id,
            name: $surname !== null ? "{$surname} family" : 'Family',
            primary_name: $family->contactName(),
            satellite: $family->homeSatellite->name,
            children: array_values($family->children->sortBy('id')->map(fn (Child $c): DoorChildData => DoorChildData::fromModel(
                $c,
                $checkedIn[$c->id] ?? null,
                $suggestedLevels[$c->id] ?? null,
            ))->all()),
        );
    }
}
