<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Family;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A family's editable details — the registration form's family fields: what the edit form shows and what
 * UpdateFamilyDetailsJob writes. Shown only to someone who may edit the family.
 */
#[TypeScript]
final class FamilyDetailsData extends Data
{
    public function __construct(
        public int $homeBranchId,
        public ?string $areaText,
        public ?string $fbAccount,
        public bool $wantsFbGroup,
        public bool $inDgroup,
        public ?string $dgroupAreaText,
        public ?string $dgroupLeaderName,
        public ?string $dgroupLeaderPhone,
        public bool $wantsDgroup,
        public ?string $lifestage,
        public ?string $notes,
    ) {}

    public static function fromModel(Family $family): self
    {
        return new self(
            homeBranchId: $family->home_branch_id,
            areaText: $family->area_text,
            fbAccount: $family->fb_account,
            wantsFbGroup: $family->wants_fb_group,
            inDgroup: $family->in_dgroup,
            dgroupAreaText: $family->dgroup_area_text,
            dgroupLeaderName: $family->dgroup_leader_name,
            dgroupLeaderPhone: $family->dgroup_leader_phone,
            wantsDgroup: $family->wants_dgroup,
            lifestage: $family->lifestage,
            notes: $family->notes,
        );
    }
}
