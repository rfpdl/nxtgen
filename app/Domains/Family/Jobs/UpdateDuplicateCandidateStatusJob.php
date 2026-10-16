<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Enums\DuplicateStatusEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Record a reviewer's answer: dismissed (not the same) or confirmed (the same — flagged for the later merge;
 * nothing is merged here), with who and when — and note it on the NEW family's history, naming the other family.
 *
 * @extends BaseJob<DuplicateCandidate>
 */
final class UpdateDuplicateCandidateStatusJob extends BaseJob
{
    public function __construct(
        private readonly DuplicateCandidate $pair,
        private readonly DuplicateStatusEnum $status,
        private readonly User $by,
    ) {}

    public function handle(): DuplicateCandidate
    {
        $this->pair->update(['status' => $this->status, 'resolved_by' => $this->by->id, 'resolved_at' => now()]);

        $other = DuplicateCandidate::familyOf($this->pair->candidate)->contactName();
        Note::system(
            about: DuplicateCandidate::familyOf($this->pair->subject),
            body: "Possible duplicate of {$other} (".mb_strtolower($this->pair->reason->label())."): {$this->status->answer()}.",
            by: $this->by,
        );

        return $this->pair;
    }
}
