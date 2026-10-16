<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Attributes\NonClientFacing;
use App\Foundation\Support\EmailAddress;

/** Everything one registration carries — the single shape used by parent, staff and import (plan §4.2). */
#[NonClientFacing]
final readonly class FamilyRegistrationData
{
    /** @param list<ChildDetailsData> $children */
    public function __construct(
        public Church $church,
        public Satellite $home_satellite,
        public GuardianDetailsData $primary,
        public array $children,
        public ?string $area_text,
        public bool $privacy_consented,
        public RegistrationSourceEnum $source,
        public ?User $registered_by = null,
        public ?string $fb_account = null,
        public bool $wants_fb_group = false,
        public bool $in_dgroup = false,
        public ?string $dgroup_area_text = null,
        public ?string $dgroup_leader_name = null,
        public ?string $dgroup_leader_phone = null,
        public bool $wants_dgroup = false,
        public ?string $lifestage = null,
        public ?string $notes = null,
        public bool $notify = true,
    ) {}

    public function normalisedEmail(): string
    {
        return EmailAddress::normalise($this->primary->email);
    }
}
