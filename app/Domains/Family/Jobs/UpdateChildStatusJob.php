<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Set a child's status (active / inactive / graduated) with who and when, and — when it changed — note it on the
 * child's history (plan §4.4). The caller checks who may.
 *
 * @extends BaseJob<Child>
 */
final class UpdateChildStatusJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly ChildStatusEnum $status,
        private readonly User $by,
    ) {}

    public function handle(): Child
    {
        $from = $this->child->status;
        $this->child->forceFill(['status' => $this->status, 'status_changed_at' => now(), 'status_changed_by' => $this->by->id])->save();

        if ($from !== $this->status) {
            Note::system(
                about: $this->child,
                body: "{$this->child->displayName()}: status changed from {$from->label()} to {$this->status->label()}.",
                by: $this->by,
            );
        }

        return $this->child;
    }
}
