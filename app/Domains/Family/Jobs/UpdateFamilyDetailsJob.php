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
            'home_satellite_id' => $d->homeSatelliteId,
            'area_text' => self::clean($d->areaText),
            'fb_account' => self::clean($d->fbAccount),
            'wants_fb_group' => $d->wantsFbGroup,
            'in_dgroup' => $d->inDgroup,
            'dgroup_area_text' => self::clean($d->dgroupAreaText),
            'dgroup_leader_name' => self::clean($d->dgroupLeaderName),
            'dgroup_leader_phone' => self::clean($d->dgroupLeaderPhone),
            'wants_dgroup' => $d->wantsDgroup,
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
