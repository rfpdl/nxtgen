<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<Child> */
final class UpdateChildStatusJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly ChildStatusEnum $status,
        private readonly User $by,
    ) {}

    public function handle(): Child
    {
        $this->child->forceFill(['status' => $this->status, 'status_changed_at' => now(), 'status_changed_by' => $this->by->id])->save();

        return $this->child;
    }
}
