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
        public int $home_satellite_id,
        public ?string $area_text,
        public ?string $fb_account,
        public bool $wants_fb_group,
        public bool $in_dgroup,
        public ?string $dgroup_area_text,
        public ?string $dgroup_leader_name,
        public ?string $dgroup_leader_phone,
        public bool $wants_dgroup,
        public ?string $lifestage,
        public ?string $notes,
    ) {}

    public static function fromModel(Family $family): self
    {
        return new self(
            home_satellite_id: $family->home_satellite_id,
            area_text: $family->area_text,
            fb_account: $family->fb_account,
            wants_fb_group: $family->wants_fb_group,
            in_dgroup: $family->in_dgroup,
            dgroup_area_text: $family->dgroup_area_text,
            dgroup_leader_name: $family->dgroup_leader_name,
            dgroup_leader_phone: $family->dgroup_leader_phone,
            wants_dgroup: $family->wants_dgroup,
            lifestage: $family->lifestage,
            notes: $family->notes,
        );
    }
}
