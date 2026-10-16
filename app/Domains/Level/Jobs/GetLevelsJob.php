<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Domains\Level\DataObjects\LevelOptionData;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\LevelChart;

/**
 * The church-wide Levels, lowest first (Level 1, Level 2, …) — for a list filter or an admin's correction. One list
 * for every Church and Satellite (DECISIONS 2026-10-17), so it needs no church.
 *
 * @extends BaseJob<list<LevelOptionData>>
 */
final class GetLevelsJob extends BaseJob
{
    /** @return list<LevelOptionData> */
    public function handle(): array
    {
        return array_map(fn (int $level): LevelOptionData => LevelOptionData::fromLevel($level), LevelChart::levels());
    }
}
