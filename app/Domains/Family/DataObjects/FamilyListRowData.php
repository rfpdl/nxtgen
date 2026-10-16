<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Family;
use App\Foundation\Support\EmailMask;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One family in the staff list. The email is shown in full only to someone who may edit the family. */
#[TypeScript]
final class FamilyListRowData extends Data
{
    /** @param list<string> $children */
    public function __construct(
        public int $id,
        public string $primaryName,
        public string $email,
        public bool $emailConfirmed,
        public string $satellite,
        public array $children,
        public string $registeredBy,
        public string $registeredOn,
    ) {}

    public static function fromModel(Family $family, bool $fullEmail): self
    {
        $email = $family->contactEmail();

        return new self(
            id: $family->id,
            primaryName: $family->contactName(),
            email: $fullEmail ? $email : EmailMask::mask($email),
            emailConfirmed: $family->contactEmailConfirmed(),
            satellite: $family->homeSatellite->name,
            children: array_values($family->children->map(fn ($c): string => $c->nickname)->all()),
            registeredBy: $family->registration_source->label(),
            registeredOn: $family->created_at?->toDateString() ?? '',
        );
    }
}
