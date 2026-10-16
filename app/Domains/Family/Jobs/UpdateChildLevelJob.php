<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Put a child at a Level, recording who and when (plan §3.2: a church admin's correction outside Moving-Up).
 *
 * @extends BaseJob<Child>
 */
final class UpdateChildLevelJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly int $level,
        private readonly User $by,
    ) {}

    public function handle(): Child
    {
        $this->child->forceFill(['level' => $this->level, 'level_set_at' => now(), 'level_set_by' => $this->by->id])->save();

        return $this->child;
    }
}
