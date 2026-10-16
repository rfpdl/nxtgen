<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Enums\RegistrationSourceEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The registration form — parent self-registration AND staff registering for a parent (plan §6 R1):
 * today's Google Form fields + home satellite.
 * The satellite must belong to THIS church — an id from the body never reaches another church.
 */
#[TypeScript]
final class RegisterFamilyInput extends Data
{
    /** @param list<ChildInput> $children */
    public function __construct(
        public int $home_satellite_id,
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
    public function toRegistration(Church $church, Satellite $homeSatellite, RegistrationSourceEnum $source, ?User $registeredBy = null): FamilyRegistrationData
    {
        return new FamilyRegistrationData(
            church: $church,
            home_satellite: $homeSatellite,
            primary: $this->guardian->toDetails(),
            children: array_map(fn (ChildInput $c) => $c->toDetails(), $this->children),
            area_text: $this->area_text,
            privacy_consented: $this->privacy_consented,
            source: $source,
            registered_by: $registeredBy,
            fb_account: $this->fb_account,
            wants_fb_group: $this->wants_fb_group,
            in_dgroup: $this->in_dgroup,
            dgroup_area_text: $this->dgroup_area_text,
            dgroup_leader_name: $this->dgroup_leader_name,
            dgroup_leader_phone: $this->dgroup_leader_phone,
            wants_dgroup: $this->wants_dgroup,
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
            // Laravel's own wording from when the field was `parent_notes` (renamed in refactor 06), kept word for
            // word. Messages, not a wildcard attribute name, because an attribute cannot do it: a nested Data's
            // attributes() win over its parent's for the same key, and ChildInput's must say "parent notes" for the
            // add/edit-a-child forms, which post a child on its own. `:index` gives back the "children.0." the
            // old default message had.
            'children.*.guardian_notes.string' => 'The children.:index.parent notes field must be a string.',
            'children.*.guardian_notes.max' => 'The children.:index.parent notes field must not be greater than :max characters.',
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
