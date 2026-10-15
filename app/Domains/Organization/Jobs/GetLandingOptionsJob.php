<?php

declare(strict_types=1);

namespace App\Domains\Organization\Jobs;

use App\Data\Models\Guardian;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Organization\DataObjects\LandingOptionData;
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
        $familyOrgIds = Guardian::query()->where('user_id', $this->user->id)->get(['organization_id'])
            ->map(fn (Guardian $g): int => $g->organization_id)->all();
        $staffOrgIds = RoleAssignment::query()->where('user_id', $this->user->id)->where('is_active', true)->get(['organization_id'])
            ->map(fn (RoleAssignment $r): int => $r->organization_id)->all();

        $orgs = Organization::query()
            ->whereIn('id', array_unique([...$familyOrgIds, ...$staffOrgIds]))
            ->orderBy('name')
            ->get();

        $options = [];
        foreach ($orgs as $org) {
            if (in_array($org->id, $familyOrgIds, true)) {
                $options[] = new LandingOptionData(churchName: $org->name, label: 'My family', url: "/{$org->slug}/family");
            }
            if (in_array($org->id, $staffOrgIds, true)) {
                $options[] = new LandingOptionData(churchName: $org->name, label: 'Serve', url: "/{$org->slug}");
            }
        }

        return $options;
    }
}
