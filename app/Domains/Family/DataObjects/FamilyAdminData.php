<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Family;
use App\Foundation\Support\EmailMask;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A family as staff in scope see it (plan §3.3 FamilyAdminData). Health details are staff-visible. */
#[TypeScript]
final class FamilyAdminData extends Data
{
    /**
     * @param  list<GuardianPortalData>  $guardians
     * @param  list<ChildPortalData>  $children
     */
    public function __construct(
        public int $id,
        public string $primaryName,
        public string $email,
        public bool $emailConfirmed,
        public string $satellite,
        public ?string $area,
        public string $registeredBy,
        public array $guardians,
        public array $children,
        public bool $canEdit,
    ) {}

    public static function fromModel(Family $family, bool $canEdit): self
    {
        $email = $family->contactEmail();

        return new self(
            id: $family->id,
            primaryName: $family->contactName(),
            email: $canEdit ? $email : EmailMask::mask($email),
            emailConfirmed: $family->contactEmailConfirmed(),
            satellite: $family->homeBranch->name,
            area: $family->area_text,
            registeredBy: $family->registration_source->label(),
            guardians: array_values($family->guardians->map(fn ($g) => GuardianPortalData::fromModel($g))->all()),
            children: array_values($family->children->sortBy('date_of_birth')->map(fn ($c) => ChildPortalData::fromModel($c))->all()),
            canEdit: $canEdit,
        );
    }
}
