<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Guardian;
use App\Enums\RelationshipEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A parent's editable details: what the edit form shows and what UpdateGuardianDetailsJob writes. No email —
 * that changes only through the confirm-by-link flow (plan §4.4), which is why this is not GuardianDetailsData.
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
    ) {}

    public static function fromModel(Guardian $guardian): self
    {
        return new self(
            first_name: $guardian->first_name,
            last_name: $guardian->last_name,
            relationship: $guardian->relationship,
            phone: (string) $guardian->phone,
            ministries: $guardian->ministries ?? [],
        );
    }
}
