<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Guardian;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\LandingOptionData;
use App\Foundation\Jobs\BaseJob;

/**
 * Where this login can go (plan §4.5): a family page for each church where they are a linked guardian,
 * and a staff dashboard for each church where they hold an active role. Family first, then staff.
 *
 * @extends BaseJob<list<LandingOptionData>>
 */
final class GetLandingOptionsJob extends BaseJob
{
    public function __construct(private readonly User $user) {}

    /** @return list<LandingOptionData> */
    public function handle(): array
    {
        $familyChurchIds = Guardian::query()->where('user_id', $this->user->id)->get(['church_id'])
            ->map(fn (Guardian $g): int => $g->church_id)->all();
        $staffChurchIds = RoleAssignment::query()->where('user_id', $this->user->id)->where('is_active', true)->get(['church_id'])
            ->map(fn (RoleAssignment $r): int => $r->church_id)->all();

        $churches = Church::query()
            ->whereIn('id', array_unique([...$familyChurchIds, ...$staffChurchIds]))
            ->orderBy('name')
            ->get();

        $options = [];
        foreach ($churches as $church) {
            if (in_array($church->id, $familyChurchIds, true)) {
                $options[] = new LandingOptionData(church_name: $church->name, label: 'My family', url: "/{$church->slug}/family");
            }
            if (in_array($church->id, $staffChurchIds, true)) {
                $options[] = new LandingOptionData(church_name: $church->name, label: 'Serve', url: "/{$church->slug}");
            }
        }

        return $options;
    }
}
