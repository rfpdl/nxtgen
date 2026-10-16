<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Foundation\Support\RouteChurch;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Editing a family — by the parent on their family page, or by staff who may edit it (plan §3.2): the
 * registration form's family fields plus the parent's own details (never the email, plan §4.4).
 *
 * The ONE owner of the family-field rules: RegisterFamilyInput spreads them, so the two forms cannot drift
 * (they did once — review-spar R4). The satellite must belong to THIS church.
 */
final class FamilyDetailsInput extends Data
{
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
        ];
    }

    public function toDetails(): FamilyDetailsData
    {
        return new FamilyDetailsData(
            homeSatelliteId: $this->home_satellite_id,
            areaText: $this->area_text,
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
}
