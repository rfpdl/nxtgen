<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;

/** One row of the existing Google Form registry (one CHILD, parent details repeated), normalised. */
final readonly class RegistryRowData
{
    public function __construct(
        public int $rowNumber,
        public string $sourceKey,
        public string $childFirstName,
        public string $childLastName,
        public string $nickname,
        public ?GenderEnum $gender,
        public ?string $dateOfBirth,
        public ?string $allergies,
        public ?string $dislikes,
        public ?string $fears,
        public ?string $parentNotes,
        public bool $wantsFbGroup,
        public ?string $fbAccount,
        public string $guardianFirstName,
        public string $guardianLastName,
        public RelationshipEnum $relationship,
        public ?string $phone,
        public string $phoneKey,
        public string $email,
        public ?string $area,
        public bool $inDgroup,
        public ?string $dgroupArea,
        public ?string $dgroupLeaderName,
        public ?string $dgroupLeaderPhone,
        public ?string $ministry,
        public bool $wantsDgroup,
        public ?string $lifestage,
        public bool $privacyConsented,
        public string $schedule,
        public ChildStatusEnum $status,
        public bool $isDuplicate,
    ) {}
}
