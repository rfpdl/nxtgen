<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Enums\RelationshipEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A parent's editable details: what the edit form shows and what UpdateGuardianDetailsJob writes. No email —
 * that changes only through the confirm-by-link flow (plan §4.4), which is why this is not GuardianDetailsData.
 * `id` names which of the family's other guardians a row is (null for one being added; UpdateFamilyGuardiansJob).
 */
#[TypeScript]
final class GuardianEditData extends Data
{
    /** @param list<string> $ministries */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public RelationshipEnum $relationship,
        public string $phone,
        public array $ministries,
        public ?int $id = null,
    ) {}

    /**
     * The family's other guardians — everyone but the primary — as the edit form lists them, oldest first.
     *
     * @return list<self>
     */
    public static function othersOf(Family $family): array
    {
        return array_values($family->guardians
            ->where('id', '!=', $family->primary_guardian_id)
            ->sortBy('id')
            ->map(fn (Guardian $g): self => self::fromModel($g))
            ->all());
    }

    /**
     * From what a form typed: names and phone trimmed, blank ministries dropped — the ONE clean-up, for the primary's
     * details (GuardianDetailsInput) and the other guardians' (OtherGuardianInput) alike.
     *
     * @param  list<string>  $ministries
     */
    public static function fromForm(string $firstName, string $lastName, RelationshipEnum $relationship, string $phone, array $ministries, ?int $id = null): self
    {
        return new self(
            first_name: trim($firstName),
            last_name: trim($lastName),
            relationship: $relationship,
            phone: trim($phone),
            ministries: array_values(array_filter(array_map('trim', $ministries), fn (string $m): bool => $m !== '')),
            id: $id,
        );
    }

    public static function fromModel(Guardian $guardian): self
    {
        return new self(
            first_name: $guardian->first_name,
            last_name: $guardian->last_name,
            relationship: $guardian->relationship,
            phone: (string) $guardian->phone,
            ministries: $guardian->ministries ?? [],
            id: $guardian->id,
        );
    }
}
