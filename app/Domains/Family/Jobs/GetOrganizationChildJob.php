<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Foundation\Jobs\BaseJob;

/**
 * A child by id, ONLY within this church.
 *
 * @extends BaseJob<Child>
 */
final class GetOrganizationChildJob extends BaseJob
{
    public function __construct(
        private readonly int $organizationId,
        private readonly int $childId,
    ) {}

    public function handle(): Child
    {
        return Child::query()->with('family')->where('organization_id', $this->organizationId)->findOrFail($this->childId);
    }
}
