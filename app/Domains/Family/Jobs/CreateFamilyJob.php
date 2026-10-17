<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<Family> */
final class CreateFamilyJob extends BaseJob
{
    public function __construct(private readonly FamilyRegistrationData $registration) {}

    public function handle(): Family
    {
        $r = $this->registration;

        return Family::query()->create([
            'church_id' => $r->church->id,
            'home_satellite_id' => $r->homeSatellite->id,
            'area_text' => self::clean($r->areaText),
            'fb_account' => self::clean($r->fbAccount),
            'wants_fb_group' => $r->wantsFbGroup,
            'in_dgroup' => $r->inDgroup,
            'dgroup_area_text' => self::clean($r->dgroupAreaText),
            'dgroup_leader_name' => self::clean($r->dgroupLeaderName),
            'dgroup_leader_phone' => self::clean($r->dgroupLeaderPhone),
            'wants_dgroup' => $r->wantsDgroup,
            'lifestage' => self::clean($r->lifestage),
            'privacy_consented_at' => $r->privacyConsented ? now() : null,
            'registered_by_user_id' => $r->registeredBy?->id,
            'registration_source' => $r->source,
            'notes' => self::clean($r->notes),
        ]);
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
