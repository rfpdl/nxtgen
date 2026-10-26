<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Child;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One child on the staff children list (plan §6 "families/children lists"). Only for staff who can see the child's
 * family; the allergy is a FLAG here — the details are on the family page. `can_resend`: the family's email is
 * unconfirmed and the viewer may re-send the link (AccessScope::canResendLink).
 */
#[TypeScript]
final class ChildListRowData extends Data
{
    public function __construct(
        public int $id,
        public string $nickname,
        public string $full_name,
        public int $age,
        public string $date_of_birth,
        public ?string $level,
        public string $status_label,
        public bool $has_allergies,
        public string $satellite,
        public int $family_id,
        public bool $can_resend,
    ) {}

    public static function fromModel(Child $child, bool $mayResend): self
    {
        return new self(
            id: $child->id,
            nickname: $child->nickname,
            full_name: $child->fullName(),
            age: (int) $child->date_of_birth->diffInYears(now()),
            date_of_birth: $child->date_of_birth->toDateString(),
            level: $child->levelName(),
            status_label: $child->status->label(),
            has_allergies: $child->allergies !== null,
            satellite: $child->family->homeSatellite->name,
            family_id: $child->family_id,
            can_resend: $mayResend && ! $child->family->contactEmailConfirmed(),
        );
    }
}
