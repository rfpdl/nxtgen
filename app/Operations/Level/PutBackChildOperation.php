<?php

declare(strict_types=1);

namespace App\Operations\Level;

use App\Data\Models\MovingUp;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetChurchForUpdateJob;
use App\Domains\Family\Jobs\GetChurchChildJob;
use App\Domains\Family\Jobs\GetFamilyForUpdateJob;
use App\Domains\Family\Jobs\UpdateChildLevelJob;
use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Domains\Level\Jobs\GetMovingUpChildJob;
use App\Domains\Level\Jobs\UpdateMovingUpChildAsPutBackJob;
use App\Enums\PutBackResultEnum;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Put one child of a Moving-Up back (Roy 2026-11-06): to the Level and status they had before it, each change noted
 * on their history, the child marked put back (who, when). Only if they are still where Moving-Up left them — a Level
 * or status someone changed since stands, and the answer says so. Once only. The caller checks it is the satellite's last
 * Moving-Up and who may; the church's row is locked, as Moving-Up locks it, and the family's: a child whose family moved
 * to another satellite since is left as they are (Elsewhere).
 *
 * @extends Operation<PutBackResultEnum>
 */
final class PutBackChildOperation extends Operation
{
    public function __construct(
        private readonly Satellite $satellite,
        private readonly MovingUp $movingUp,
        private readonly int $childId,
        private readonly User $by,
    ) {}

    public function handle(): PutBackResultEnum
    {
        // The church read BEFORE the transaction, so the lock is its first read (as MoveChildrenUpOperation).
        $church = $this->satellite->church;

        return DB::transaction(function () use ($church): PutBackResultEnum {
            $this->run(new GetChurchForUpdateJob(church: $church));
            $child = $this->run(new GetChurchChildJob(church: $church, id: $this->childId));
            $moved = $this->run(new GetMovingUpChildJob(church: $church, movingUp: $this->movingUp, child: $child));

            // Only while the family is still this satellite's: one that moved since is the other satellite's now. Its
            // row locked, so a move waits for this (Astra, issue 06).
            if ($this->run(new GetFamilyForUpdateJob(family: $child->family))->home_satellite_id !== $this->satellite->id) {
                return PutBackResultEnum::Elsewhere;
            }

            if ($moved->put_back_at !== null) {
                return PutBackResultEnum::AlreadyPutBack;
            }
            if (! $moved->isWhereMovingUpLeft($child)) {
                return PutBackResultEnum::ChangedSince;
            }

            if ($child->level !== $moved->from_level) {
                $this->run(new UpdateChildLevelJob(child: $child, level: $moved->from_level, by: $this->by));
            }
            if ($child->status !== $moved->from_status) {
                $this->run(new UpdateChildStatusJob(child: $child, status: $moved->from_status, by: $this->by));
            }
            $this->run(new UpdateMovingUpChildAsPutBackJob(moved: $moved, by: $this->by));

            return PutBackResultEnum::PutBack;
        });
    }
}
