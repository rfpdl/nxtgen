<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Card\DataObjects\CardRequestData;
use App\Domains\CheckIn\DataObjects\ChildAttendanceData;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Family\DataObjects\DuplicatePairData;
use App\Domains\Family\DataObjects\FamilyAdminData;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\DataObjects\NoteData;
use App\Domains\Level\DataObjects\LevelOptionData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A family's page for staff (Church/Families/Show): the family, its attendance, Card requests, notes and possible duplicates — and, for whoever may edit it, its details and choices — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class StaffFamilyPageData extends Data
{
    public function __construct(
        public FamilyAdminData $family,
        /** @var list<ChildAttendanceData> each child's attendance, newest first (slice 3): staff only */
        public array $attendance,
        /** @var list<CardRequestData> each child's open Card request, at most one per child: staff only */
        public array $card_requests,
        /** @var list<NoteData> what staff changed on this family and its children, newest first */
        public array $notes,
        /** @var list<DuplicatePairData> open possible duplicates, for an admin who may answer them; empty for everyone else */
        public array $duplicates,
        /** only for someone who may edit this family */
        public ?FamilyDetailsData $details,
        public ?GuardianEditData $primary,
        /** @var list<GuardianEditData> */
        public array $other_guardians,
        /** @var list<SatelliteOptionData> where the family may be moved */
        public array $satellites,
        /** correct the email, set a child's status, delete a photo for good: an editor, in Admin only */
        public bool $admin_controls,
        /** satellite admins only, in Admin: correct a child's Level outside Moving-Up */
        public bool $can_set_level,
        /** @var list<LevelOptionData> */
        public array $levels,
        /** @var array<string, list<array{value: string, label: string, color: string}>> the enums its forms read with useEnum() — page-wide, not a prop of the page */
        #[Hidden]
        public array $enums,
    ) {}
}
