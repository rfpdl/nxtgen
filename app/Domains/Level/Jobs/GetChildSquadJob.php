<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\LevelChart;
use LogicException;

/**
 * The child → Squad lookup (DECISIONS 2026-10-17, built with check-in): THIS satellite's Squad for the child's Level
 * and gender (Level 1 mixed) — null when the satellite does not have it (CreateChildSquadJob makes it). A visiting
 * child joins the Squad of the satellite they are checked in at, so visiting needs nothing.
 *
 * @extends BaseJob<Squad|null>
 */
final class GetChildSquadJob extends BaseJob
{
    public function __construct(
        private readonly Satellite $satellite,
        private readonly Child $child,
    ) {}

    public function handle(): ?Squad
    {
        $level = $this->child->level ?? throw new LogicException("Child {$this->child->id} has no Level: pick one before checking in.");

        return Squad::query()
            ->where('church_id', $this->satellite->church_id)
            ->where('satellite_id', $this->satellite->id)
            ->where('level', $level)
            ->where('gender', LevelChart::squadGender($level, $this->child->gender))
            ->first();
    }
}
