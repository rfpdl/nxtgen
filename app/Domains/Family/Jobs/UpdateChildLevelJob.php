<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\LevelChart;

/**
 * Put a child at a Level, recording who and when (plan §3.2: a church admin's correction outside Moving-Up), and note
 * the change on the child's history when the Level changed (as UpdateChildStatusJob does for a status).
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
        $from = $this->child->level;
        $this->child->forceFill(['level' => $this->level, 'level_set_at' => now(), 'level_set_by' => $this->by->id])->save();

        if ($from !== $this->level) {
            Note::system(
                about: $this->child,
                body: "{$this->child->displayName()}: Level changed from ".($from === null ? 'no Level' : LevelChart::name($from)).' to '.LevelChart::name($this->level).'.',
                by: $this->by,
            );
        }

        return $this->child;
    }
}
