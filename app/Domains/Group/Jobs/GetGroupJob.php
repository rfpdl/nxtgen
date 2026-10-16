<?php

declare(strict_types=1);

namespace App\Domains\Group\Jobs;

use App\Data\Models\Group;
use App\Foundation\Jobs\BaseJob;

/**
 * An ACTIVE group by id, ONLY within this church — or null. Retired groups are never assigned (review 4).
 *
 * @extends BaseJob<Group|null>
 */
final class GetGroupJob extends BaseJob
{
    public function __construct(
        private readonly int $organizationId,
        private readonly int $id,
    ) {}

    public function handle(): ?Group
    {
        return Group::query()->where('organization_id', $this->organizationId)->where('is_active', true)->find($this->id);
    }
}
