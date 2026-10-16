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
            'home_satellite_id' => $r->home_satellite->id,
            'area_text' => self::clean($r->area_text),
            'fb_account' => self::clean($r->fb_account),
            'wants_fb_group' => $r->wants_fb_group,
            'in_dgroup' => $r->in_dgroup,
            'dgroup_area_text' => self::clean($r->dgroup_area_text),
            'dgroup_leader_name' => self::clean($r->dgroup_leader_name),
            'dgroup_leader_phone' => self::clean($r->dgroup_leader_phone),
            'wants_dgroup' => $r->wants_dgroup,
            'lifestage' => self::clean($r->lifestage),
            'privacy_consented_at' => $r->privacy_consented ? now() : null,
            'registered_by_user_id' => $r->registered_by?->id,
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
