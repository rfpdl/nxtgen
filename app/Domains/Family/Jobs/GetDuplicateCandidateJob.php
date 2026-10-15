<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\DuplicateCandidate;
use App\Foundation\Jobs\BaseJob;

/**
 * One possible-duplicate pair by id, ONLY within this church, with both sides loaded.
 *
 * @extends BaseJob<DuplicateCandidate>
 */
final class GetDuplicateCandidateJob extends BaseJob
{
    public function __construct(
        private readonly int $organizationId,
        private readonly int $id,
    ) {}

    public function handle(): DuplicateCandidate
    {
        return DuplicateCandidate::query()
            ->with(['subject', 'candidate'])
            ->where('organization_id', $this->organizationId)
            ->findOrFail($this->id);
    }
}
