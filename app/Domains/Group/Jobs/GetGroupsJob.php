<?php

declare(strict_types=1);

namespace App\Domains\Group\Jobs;

use App\Data\Models\Group;
use App\Domains\Group\DataObjects\GroupOptionData;
use App\Foundation\Jobs\BaseJob;

/**
 * This church's active groups, in their own order (Level 1, Level 2 Boys, …) — e.g. for a filter.
 *
 * @extends BaseJob<list<GroupOptionData>>
 */
final class GetGroupsJob extends BaseJob
{
    public function __construct(private readonly int $churchId) {}

    /** @return list<GroupOptionData> */
    public function handle(): array
    {
        return array_values(Group::query()
            ->where('church_id', $this->churchId)
            ->where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->map(fn (Group $g): GroupOptionData => GroupOptionData::fromModel($g))
            ->all());
    }
}
