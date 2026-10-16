<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Foundation\Jobs\BaseJob;

/**
 * Save a family's details. The home satellite is trusted here: the input already checked it belongs to this
 * church. Contact email, status and claims are not touched — they have their own flows.
 *
 * @extends BaseJob<Family>
 */
final class UpdateFamilyDetailsJob extends BaseJob
{
    public function __construct(
        private readonly Family $family,
        private readonly FamilyDetailsData $details,
    ) {}

    public function handle(): Family
    {
        $d = $this->details;

        $this->family->update([
            'home_satellite_id' => $d->home_satellite_id,
            'area_text' => self::clean($d->area_text),
            'fb_account' => self::clean($d->fb_account),
            'wants_fb_group' => $d->wants_fb_group,
            'in_dgroup' => $d->in_dgroup,
            'dgroup_area_text' => self::clean($d->dgroup_area_text),
            'dgroup_leader_name' => self::clean($d->dgroup_leader_name),
            'dgroup_leader_phone' => self::clean($d->dgroup_leader_phone),
            'wants_dgroup' => $d->wants_dgroup,
            'lifestage' => self::clean($d->lifestage),
            'notes' => self::clean($d->notes),
        ]);

        return $this->family;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
