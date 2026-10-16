<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Foundation\Attributes\NonClientFacing;

/** One row of the existing Google Form registry (one CHILD, parent details repeated), normalised. */
#[NonClientFacing]
final readonly class RegistryRowData
{
    public function __construct(
        public int $row_number,
        public string $source_key,
        public string $child_first_name,
        public string $child_last_name,
        public string $nickname,
        public ?GenderEnum $gender,
        public ?string $date_of_birth,
        public ?string $allergies,
        public ?string $dislikes,
        public ?string $fears,
        public ?string $guardian_notes,
        public bool $wants_fb_group,
        public ?string $fb_account,
        public string $guardian_first_name,
        public string $guardian_last_name,
        public RelationshipEnum $relationship,
        public ?string $phone,
        public string $phone_key,
        public string $email,
        public ?string $area,
        public bool $in_dgroup,
        public ?string $dgroup_area,
        public ?string $dgroup_leader_name,
        public ?string $dgroup_leader_phone,
        public ?string $ministry,
        public bool $wants_dgroup,
        public ?string $lifestage,
        public bool $privacy_consented,
        public string $schedule,
        public ChildStatusEnum $status,
        public bool $is_duplicate,
    ) {}
}
