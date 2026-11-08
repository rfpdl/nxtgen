<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\MovingUp;
use App\Data\Models\Satellite;
use App\Foundation\Jobs\BaseJob;

/**
 * The satellite's latest Moving-Up — the only one that can be put back (DECISIONS 2026-11-08: Moving-Up per
 * satellite) — with its children (and who ran it, who put any back) for the page. None for a satellite that has never
 * moved up.
 *
 * @extends BaseJob<MovingUp|null>
 */
final class GetLastMovingUpJob extends BaseJob
{
    public function __construct(private readonly Satellite $satellite) {}

    public function handle(): ?MovingUp
    {
        return MovingUp::query()
            ->where('church_id', $this->satellite->church_id)
            ->where('satellite_id', $this->satellite->id)
            ->with(['maker', 'children' => fn ($q) => $q->where('church_id', $this->satellite->church_id)
                // Only the children still this satellite's: a family that moved since is the other satellite's now.
                ->whereHas('child.family', fn ($f) => $f->where('home_satellite_id', $this->satellite->id))->orderBy('id'), 'children.child', 'children.putBackBy'])
            ->latest('id')
            ->first();
    }
}
