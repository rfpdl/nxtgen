<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Family;
use App\Foundation\Support\EmailMask;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One family in the staff list. The email is shown in full only to someone who may edit the family. `can_resend`:
 * the email is unconfirmed and the viewer may re-send the link (AccessScope::canResendLink).
 */
#[TypeScript]
final class FamilyListRowData extends Data
{
    /** @param list<ListChildData> $children */
    public function __construct(
        public int $id,
        public string $primary_name,
        public string $email,
        public bool $email_confirmed,
        public string $satellite,
        public array $children,
        public string $registered_by,
        public string $registered_on,
        public bool $can_resend,
    ) {}

    public static function fromModel(Family $family, bool $fullEmail, bool $mayResend): self
    {
        $email = $family->contactEmail();
        $confirmed = $family->contactEmailConfirmed();

        return new self(
            id: $family->id,
            primary_name: $family->contactName(),
            email: $fullEmail ? $email : EmailMask::mask($email),
            email_confirmed: $confirmed,
            satellite: $family->homeSatellite->name,
            children: array_values($family->children->map(fn ($c): ListChildData => ListChildData::fromModel($c))->all()),
            registered_by: $family->registration_source->label(),
            registered_on: $family->created_at?->toDateString() ?? '',
            can_resend: ! $confirmed && $mayResend,
        );
    }
}
