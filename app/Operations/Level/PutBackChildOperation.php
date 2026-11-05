<?php

declare(strict_types=1);

namespace App\Operations\Level;

use App\Data\Models\Church;
use App\Data\Models\MovingUp;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetChurchForUpdateJob;
use App\Domains\Family\Jobs\GetChurchChildJob;
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
 * or status someone changed since stands, and the answer says so. Once only. The caller checks it is the church's last
 * Moving-Up and who may; the church's row is locked, as Moving-Up locks it.
 *
 * @extends Operation<PutBackResultEnum>
 */
final class PutBackChildOperation extends Operation
{
    public function __construct(
        private readonly Church $church,
        private readonly MovingUp $movingUp,
        private readonly int $childId,
        private readonly User $by,
    ) {}

    public function handle(): PutBackResultEnum
    {
        return DB::transaction(function (): PutBackResultEnum {
            $this->run(new GetChurchForUpdateJob(church: $this->church));
            $child = $this->run(new GetChurchChildJob(church: $this->church, id: $this->childId));
            $moved = $this->run(new GetMovingUpChildJob(church: $this->church, movingUp: $this->movingUp, child: $child));

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
