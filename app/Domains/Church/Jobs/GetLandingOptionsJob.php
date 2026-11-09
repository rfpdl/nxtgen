<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Guardian;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\LandingOptionData;
use App\Foundation\Jobs\BaseJob;

/**
 * Where this login can go (plan §4.5): their family page (`/family`) where they are a linked guardian, and a staff
 * home where they may open a satellite (Satellite::openTo) — the one they used last, else the only one, else "Pick
 * your satellite" (spec item 11). Family first, then staff.
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
        // The satellites they may open, by church (one church today), in the order added.
        $open = Satellite::query()->openTo($this->user)->orderBy('id')->get()->groupBy('church_id');

        $churches = Church::query()
            ->whereIn('id', array_unique([...$familyChurchIds, ...$open->keys()->all()]))
            ->orderBy('name')
            ->get();

        $options = [];
        foreach ($churches as $church) {
            if (in_array($church->id, $familyChurchIds, true)) {
                $options[] = new LandingOptionData(church_name: $church->name, label: 'My family', url: '/family', place: 'family');
            }
            $satellites = $open->get($church->id);
            if ($satellites !== null && $satellites->isNotEmpty()) {
                $options[] = new LandingOptionData(church_name: $church->name, label: 'Serve', url: $this->landingOf(array_values($satellites->all())), place: "serve:{$church->id}");
            }
        }

        return $options;
    }

    /**
     * Where staff land (spec item 11): the satellite they used last, while they may still open it; else the only one;
     * else "Pick your satellite".
     *
     * @param  list<Satellite>  $satellites
     */
    private function landingOf(array $satellites): string
    {
        foreach ($satellites as $satellite) {
            if ($satellite->id === $this->user->last_satellite_id) {
                return "/{$satellite->slug}";
            }
        }

        return count($satellites) === 1 ? "/{$satellites[0]->slug}" : '/pick';
    }
}
