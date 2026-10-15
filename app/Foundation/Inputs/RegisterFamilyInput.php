<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Data\Models\Branch;
use App\Data\Models\Organization;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Enums\RegistrationSourceEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * The registration form — parent self-registration AND staff registering for a parent (plan §6 R1):
 * today's Google Form fields + home satellite.
 * The satellite must belong to THIS church — an id from the body never reaches another church.
 */
final class RegisterFamilyInput extends Data
{
    /** @param list<ChildInput> $children */
    public function __construct(
        public int $home_branch_id,
        public ?string $area_text,
        public bool $privacy_consented,
        public GuardianInput $guardian,
        #[DataCollectionOf(ChildInput::class)]
        public array $children,
        public ?string $fb_account = null,
        public bool $wants_fb_group = false,
        public bool $in_dgroup = false,
        public ?string $dgroup_area_text = null,
        public ?string $dgroup_leader_name = null,
        public ?string $dgroup_leader_phone = null,
        public bool $wants_dgroup = false,
        public ?string $lifestage = null,
        public ?string $notes = null,
    ) {}

    /**
     * The ONE mapping from the form to a registration, used by parent self-registration and staff registration
     * alike — two copies drifted once already (staff dropped DGroup area, leader phone and lifestage).
     */
    public function toRegistration(Organization $organization, Branch $homeBranch, RegistrationSourceEnum $source, ?User $registeredBy = null): FamilyRegistrationData
    {
        return new FamilyRegistrationData(
            organization: $organization,
            homeBranch: $homeBranch,
            primary: $this->guardian->toDetails(),
            children: array_map(fn (ChildInput $c) => $c->toDetails(), $this->children),
            areaText: $this->area_text,
            privacyConsented: $this->privacy_consented,
            source: $source,
            registeredBy: $registeredBy,
            fbAccount: $this->fb_account,
            wantsFbGroup: $this->wants_fb_group,
            inDgroup: $this->in_dgroup,
            dgroupAreaText: $this->dgroup_area_text,
            dgroupLeaderName: $this->dgroup_leader_name,
            dgroupLeaderPhone: $this->dgroup_leader_phone,
            wantsDgroup: $this->wants_dgroup,
            lifestage: $this->lifestage,
            notes: $this->notes,
        );
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            ...FamilyDetailsInput::messages(),
            'privacy_consented.accepted' => 'Please agree so we can keep these details.',
            'children.required' => 'Please add at least one child.',
            'children.min' => 'Please add at least one child.',
        ];
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        // The family fields are FamilyDetailsInput's rules (one owner, shared with editing).
        return [
            ...FamilyDetailsInput::rules(),
            'privacy_consented' => ['accepted'],
            'children' => ['required', 'array', 'min:1', 'max:10'],
        ];
    }
}
