<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Foundation\Support\RouteChurch;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Editing a family — by the parent on their family page, or by staff who may edit it (plan §3.2): the
 * registration form's family fields plus the parent's own details (never the email, plan §4.4), and changes to the
 * family's other parents and guardians: the rows shown (`guardians`: changed, or new without an id) and the ids taken
 * off (`removed_guardian_ids`). A guardian mentioned in neither is not touched.
 *
 * The ONE owner of the family-field rules: RegisterFamilyInput spreads them, so the two forms cannot drift
 * (they did once — review-spar R4). The satellite must belong to THIS church.
 */
#[TypeScript]
final class FamilyDetailsInput extends Data
{
    /**
     * @param  list<OtherGuardianInput>  $guardians
     * @param  list<int>  $removed_guardian_ids
     */
    public function __construct(
        public int $home_satellite_id,
        public ?string $area_text,
        public GuardianDetailsInput $guardian,
        public ?string $fb_account = null,
        public bool $wants_fb_group = false,
        public bool $in_dgroup = false,
        public ?string $dgroup_area_text = null,
        public ?string $dgroup_leader_name = null,
        public ?string $dgroup_leader_phone = null,
        public bool $wants_dgroup = false,
        public ?string $lifestage = null,
        public ?string $notes = null,
        #[DataCollectionOf(OtherGuardianInput::class)]
        public array $guardians = [],
        public array $removed_guardian_ids = [],
    ) {}

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'home_satellite_id.required' => 'Please choose the satellite you usually attend.',
            'home_satellite_id.exists' => 'Please choose the satellite you usually attend.',
        ];
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        $churchId = RouteChurch::from(request())->id ?? 0;

        return [
            'home_satellite_id' => ['required', 'integer', Rule::exists('satellites', 'id')->where('church_id', $churchId)],
            'area_text' => ['nullable', 'string', 'max:120'],
            'fb_account' => ['nullable', 'string', 'max:120'],
            'dgroup_area_text' => ['nullable', 'string', 'max:120'],
            'dgroup_leader_name' => ['nullable', 'string', 'max:120'],
            'dgroup_leader_phone' => ['nullable', 'string', 'max:30'],
            'lifestage' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'guardians' => ['array', 'max:5'],
            'removed_guardian_ids' => ['array', 'max:20'],
            'removed_guardian_ids.*' => ['integer'],
        ];
    }

    /**
     * The other guardians' rows as the form sends them: changed, or new (no id).
     *
     * @return list<GuardianEditData>
     */
    public function otherGuardians(): array
    {
        return array_map(fn (OtherGuardianInput $g) => $g->toEdit(), $this->guardians);
    }

    /** Whether the form changes the other guardians at all. */
    public function changesOtherGuardians(): bool
    {
        return $this->guardians !== [] || $this->removed_guardian_ids !== [];
    }

    public function toDetails(): FamilyDetailsData
    {
        return new FamilyDetailsData(
            home_satellite_id: $this->home_satellite_id,
            area_text: $this->area_text,
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
}
