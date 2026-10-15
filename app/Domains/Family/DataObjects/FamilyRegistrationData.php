<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Branch;
use App\Data\Models\Organization;
use App\Data\Models\User;
use App\Enums\RegistrationSourceEnum;

/** Everything one registration carries — the single shape used by parent, staff and import (plan §4.2). */
final readonly class FamilyRegistrationData
{
    /** @param list<ChildDetailsData> $children */
    public function __construct(
        public Organization $organization,
        public Branch $homeBranch,
        public GuardianDetailsData $primary,
        public array $children,
        public ?string $areaText,
        public bool $privacyConsented,
        public RegistrationSourceEnum $source,
        public ?User $registeredBy = null,
        public ?string $fbAccount = null,
        public bool $wantsFbGroup = false,
        public bool $inDgroup = false,
        public ?string $dgroupAreaText = null,
        public ?string $dgroupLeaderName = null,
        public ?string $dgroupLeaderPhone = null,
        public bool $wantsDgroup = false,
        public ?string $lifestage = null,
        public ?string $notes = null,
        public bool $notify = true,
    ) {}

    public function normalisedEmail(): string
    {
        return mb_strtolower(trim($this->primary->email));
    }
}
