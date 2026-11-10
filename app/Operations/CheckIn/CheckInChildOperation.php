<?php

declare(strict_types=1);

namespace App\Operations\CheckIn;

use App\Data\Models\Child;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\CreateCheckInJob;
use App\Domains\CheckIn\Jobs\GetSessionForUpdateJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Family\Jobs\GetChildForUpdateJob;
use App\Domains\Family\Jobs\UpdateChildLevelJob;
use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Domains\Level\Jobs\CreateChildSquadJob;
use App\Domains\Level\Jobs\GetChildSquadJob;
use App\Enums\CheckInResultEnum;
use App\Enums\ChildStatusEnum;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Check ONE child in to a Session today, at the door (DECISIONS 2026-10-26):
 *
 * - a Session not open now → refused (spec item 15): read under its lock, with the church's date read after the lock
 *   (a request that waited past midnight sees the new day), so an admin closing it at this moment comes before or
 *   after, never half-way;
 * - Graduated → refused: no longer part of NxtGen;
 * - inactive → refused unless the door asked "Make active again?" and Staff said yes — then made active (noted);
 * - no Level → refused unless one was picked at the door — then saved (noted); a Level the child has is never changed;
 * - then this satellite's Squad for the child's Level and gender, and the Check-in — one per child per Session,
 *   however many phones press.
 *
 * Everything for the child commits together or not at all, decided on the Session and the child as they are now, both
 * rows locked (the Session first, then the child — as status and Level changes lock the child last).
 *
 * @extends Operation<CheckInResultEnum>
 */
final class CheckInChildOperation extends Operation
{
    public function __construct(
        private readonly Session $session,
        private readonly Child $child,
        private readonly ?int $level,
        private readonly bool $makeActive,
        private readonly User $by,
    ) {}

    public function handle(): CheckInResultEnum
    {
        return DB::transaction(function (): CheckInResultEnum {
            $session = $this->run(new GetSessionForUpdateJob(session: $this->session));
            if (! $session->isOpenOn($this->run(new GetChurchTodayJob(church: $session->church)))) {
                return CheckInResultEnum::Closed;
            }
            // The child as they are now, their row locked till the end — a status or Level change, or a Moving-Up, may
            // have committed since the door loaded them (Codex): decided on this, never on the page's copy.
            $child = $this->run(new GetChildForUpdateJob(child: $this->child));

            $refused = match (true) {
                $child->status === ChildStatusEnum::Graduated => CheckInResultEnum::Graduated,
                $child->status === ChildStatusEnum::Inactive && ! $this->makeActive => CheckInResultEnum::Inactive,
                $child->level === null && $this->level === null => CheckInResultEnum::NeedsLevel,
                default => null,
            };
            if ($refused !== null) {
                return $refused;
            }

            if ($child->status === ChildStatusEnum::Inactive) {
                $this->run(new UpdateChildStatusJob(child: $child, status: ChildStatusEnum::Active, by: $this->by));
            }
            if ($child->level === null && $this->level !== null) {
                $this->run(new UpdateChildLevelJob(child: $child, level: $this->level, by: $this->by));
            }

            $squad = $this->run(new GetChildSquadJob(satellite: $session->satellite, child: $child))
                ?? $this->run(new CreateChildSquadJob(satellite: $session->satellite, child: $child));

            return $this->run(new CreateCheckInJob(session: $session, child: $child, squad: $squad, by: $this->by));
        });
    }
}
