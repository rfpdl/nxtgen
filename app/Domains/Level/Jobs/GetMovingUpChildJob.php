<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\MovingUp;
use App\Data\Models\MovingUpChild;
use App\Foundation\Jobs\BaseJob;

/**
 * This child on this Moving-Up, ONLY within this church — not found if it did not move them.
 *
 * @extends BaseJob<MovingUpChild>
 */
final class GetMovingUpChildJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly MovingUp $movingUp,
        private readonly Child $child,
    ) {}

    public function handle(): MovingUpChild
    {
        return MovingUpChild::query()
            ->where('church_id', $this->church->id)
            ->where('moving_up_id', $this->movingUp->id)
            ->where('child_id', $this->child->id)
            ->firstOrFail()
            ->setRelation('child', $this->child);
    }
}
