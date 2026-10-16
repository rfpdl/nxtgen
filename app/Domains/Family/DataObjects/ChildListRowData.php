<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Child;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One child on the staff children list (plan §6 "families/children lists"). Only for staff who can see the child's
 * family; the allergy is a FLAG here — the details are on the family page.
 */
#[TypeScript]
final class ChildListRowData extends Data
{
    public function __construct(
        public int $id,
        public string $nickname,
        public string $fullName,
        public int $age,
        public string $dateOfBirth,
        public ?string $group,
        public string $status,
        public bool $hasAllergies,
        public string $satellite,
        public int $familyId,
    ) {}

    public static function fromModel(Child $child): self
    {
        return new self(
            id: $child->id,
            nickname: $child->nickname,
            fullName: trim(implode(' ', array_filter([$child->first_name, $child->middle_name, $child->last_name]))),
            age: (int) $child->date_of_birth->diffInYears(now()),
            dateOfBirth: $child->date_of_birth->toDateString(),
            group: $child->group?->name,
            status: $child->status->label(),
            hasAllergies: $child->allergies !== null,
            satellite: $child->family->homeBranch->name,
            familyId: $child->family_id,
        );
    }
}
