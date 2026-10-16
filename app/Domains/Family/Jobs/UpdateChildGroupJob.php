<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Group;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Put a child in a group, recording who and when (plan §3.2: a church admin's correction outside Moving-Up).
 *
 * @extends BaseJob<Child>
 */
final class UpdateChildGroupJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly Group $group,
        private readonly User $by,
    ) {}

    public function handle(): Child
    {
        $this->child->forceFill(['group_id' => $this->group->id, 'group_set_at' => now(), 'group_set_by' => $this->by->id])->save();
        $this->child->setRelation('group', $this->group);

        return $this->child;
    }
}
