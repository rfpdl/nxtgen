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
        public string $primary_name,
        public string $email,
        public bool $email_confirmed,
        public string $satellite,
        public array $children,
        public string $registered_by,
        public string $registered_on,
    ) {}

    public static function fromModel(Family $family, bool $fullEmail): self
    {
        $email = $family->contactEmail();

        return new self(
            id: $family->id,
            primary_name: $family->contactName(),
            email: $fullEmail ? $email : EmailMask::mask($email),
            email_confirmed: $family->contactEmailConfirmed(),
            satellite: $family->homeSatellite->name,
            children: array_values($family->children->map(fn ($c): string => $c->nickname)->all()),
            registered_by: $family->registration_source->label(),
            registered_on: $family->created_at?->toDateString() ?? '',
        );
    }
}
