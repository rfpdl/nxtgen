<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Foundation\Jobs\BaseJob;

/**
 * The child as they are now, with their family, the row locked until the transaction ends — taken after the church's
 * lock, so a Level fix or a status change sees what a Moving-Up just committed and records the real "from".
 *
 * @extends BaseJob<Child>
 */
final class GetChildForUpdateJob extends BaseJob
{
    public function __construct(private readonly Child $child) {}

    public function handle(): Child
    {
        return Child::query()->with('family')->where('church_id', $this->child->church_id)->whereKey($this->child->id)->lockForUpdate()->sole();
    }
}
