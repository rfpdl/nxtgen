<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\LevelChart;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;

/**
 * Make THIS satellite's Squad for the child's Level and gender, the chart's way (as Squad::createChartFor does) — for
 * a satellite that lacks it (a Level added to the chart later), so the door never stops on it. Every satellite is
 * normally given its Squads when made. If another phone makes it at the same moment, the unique (satellite, level,
 * gender) key refuses the second insert and that phone's row is read with a LOCKING read (it waits for the commit;
 * a plain read inside the caller's transaction would not see it).
 *
 * @extends BaseJob<Squad>
 */
final class CreateChildSquadJob extends BaseJob
{
    public function __construct(
        private readonly Satellite $satellite,
        private readonly Child $child,
    ) {}

    public function handle(): Squad
    {
        $level = $this->child->level ?? throw new LogicException("Child {$this->child->id} has no Level: pick one before checking in.");
        $gender = LevelChart::squadGender($level, $this->child->gender);
        $church = $this->satellite->church_id;

        try {
            return Squad::query()->withSavepointIfNeeded(fn (): Squad => Squad::query()->create([
                'church_id' => $church, 'satellite_id' => $this->satellite->id, 'level' => $level, 'gender' => $gender,
            ]));
        } catch (UniqueConstraintViolationException) {
            return Squad::query()->where('church_id', $church)->where('satellite_id', $this->satellite->id)->where('level', $level)->where('gender', $gender)->lockForUpdate()->firstOrFail();
        }
    }
}
