<?php

declare(strict_types=1);

namespace App\Operations\CheckIn;

use App\Data\Models\Child;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\CreateCheckInJob;
use App\Domains\CheckIn\Jobs\CreateSessionJob;
use App\Domains\Family\Jobs\UpdateChildLevelJob;
use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Domains\Level\Jobs\CreateChildSquadJob;
use App\Domains\Level\Jobs\GetChildSquadJob;
use App\Enums\CheckInResultEnum;
use App\Enums\ChildStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Check ONE child in to a class today, at the door (DECISIONS 2026-10-26):
 *
 * - Graduated → refused: no longer part of NxtGen;
 * - inactive → refused unless the door asked "Make active again?" and Staff said yes — then made active (noted);
 * - no Level → refused unless one was picked at the door — then saved (noted); a Level the child has is never changed;
 * - then the class's Session for today (made by its first Check-in), this satellite's Squad for the child's Level and
 *   gender, and the Check-in — one per child per Session, however many phones press.
 *
 * Everything for the child commits together or not at all.
 *
 * @extends Operation<CheckInResultEnum>
 */
final class CheckInChildOperation extends Operation
{
    public function __construct(
        private readonly Schedule $schedule,
        private readonly CarbonImmutable $today,
        private readonly Child $child,
        private readonly ?int $level,
        private readonly bool $makeActive,
        private readonly User $by,
    ) {}

    public function handle(): CheckInResultEnum
    {
        $refused = match (true) {
            $this->child->status === ChildStatusEnum::Graduated => CheckInResultEnum::Graduated,
            $this->child->status === ChildStatusEnum::Inactive && ! $this->makeActive => CheckInResultEnum::Inactive,
            $this->child->level === null && $this->level === null => CheckInResultEnum::NeedsLevel,
            default => null,
        };
        if ($refused !== null) {
            return $refused;
        }

        return DB::transaction(function (): CheckInResultEnum {
            if ($this->child->status === ChildStatusEnum::Inactive) {
                $this->run(new UpdateChildStatusJob(child: $this->child, status: ChildStatusEnum::Active, by: $this->by));
            }
            if ($this->child->level === null && $this->level !== null) {
                $this->run(new UpdateChildLevelJob(child: $this->child, level: $this->level, by: $this->by));
            }

            $session = $this->run(new CreateSessionJob(schedule: $this->schedule, date: $this->today));
            $squad = $this->run(new GetChildSquadJob(satellite: $this->schedule->satellite, child: $this->child))
                ?? $this->run(new CreateChildSquadJob(satellite: $this->schedule->satellite, child: $this->child));

            return $this->run(new CreateCheckInJob(session: $session, child: $this->child, squad: $squad, by: $this->by));
        });
    }
}
