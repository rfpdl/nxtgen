<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Guardian;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\LandingOptionData;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Where this login can go (plan §4.5): their family page (`/family`) where they are a linked guardian, and a staff
 * home where they hold an active Role — the first satellite their Roles cover, in the order satellites were added
 * (the satellite picker and the last one used are issue 08). Family first, then staff.
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
        $roles = RoleAssignment::query()->where('user_id', $this->user->id)->where('is_active', true)->get();
        $staffChurchIds = $roles->map(fn (RoleAssignment $r): int => $r->church_id)->all();

        $churches = Church::query()
            ->whereIn('id', array_unique([...$familyChurchIds, ...$staffChurchIds]))
            ->orderBy('name')
            ->get();

        $options = [];
        foreach ($churches as $church) {
            if (in_array($church->id, $familyChurchIds, true)) {
                $options[] = new LandingOptionData(church_name: $church->name, label: 'My family', url: '/family');
            }
            $satellite = $this->firstSatellite($church, array_values($roles->where('church_id', $church->id)->all()));
            if ($satellite !== null) {
                $options[] = new LandingOptionData(church_name: $church->name, label: 'Serve', url: "/{$satellite->slug}");
            }
        }

        // The platform admin (spec item 12), with no Role: the first satellite, until their own page (issue 09).
        if ($this->user->is_platform_admin && ! in_array('Serve', array_map(fn (LandingOptionData $o): string => $o->label, $options), true)) {
            $first = Satellite::query()->with('church')->orderBy('id')->first();
            if ($first !== null) {
                $options[] = new LandingOptionData(church_name: $first->church->name, label: 'Serve', url: "/{$first->slug}");
            }
        }

        return $options;
    }

    /**
     * The first satellite, in the order they were added, that these Roles cover: a satellite Role's own, a class Role's
     * class's.
     *
     * @param  list<RoleAssignment>  $roles
     */
    private function firstSatellite(Church $church, array $roles): ?Satellite
    {
        if ($roles === []) {
            return null;
        }

        $of = fn (ScopeTypeEnum $type): array => array_values(array_map(fn (RoleAssignment $r): int => $r->scope_id, array_filter($roles, fn (RoleAssignment $r): bool => $r->scope_type === $type)));

        return Satellite::query()
            ->where('church_id', $church->id)
            ->where(fn ($q) => $q
                ->whereIn('id', $of(ScopeTypeEnum::Satellite))
                ->orWhereIn('id', Schedule::query()->where('church_id', $church->id)->where('is_active', true)->whereIn('id', $of(ScopeTypeEnum::Schedule))->select('satellite_id')))
            ->orderBy('id')
            ->first();
    }
}
