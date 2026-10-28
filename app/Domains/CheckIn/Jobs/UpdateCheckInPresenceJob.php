<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Roll call (GLOSSARY "Roll call"): mark a checked-in child present in their Squad, saying who and when — or unmark
 * them, clearing both. Latest wins, no history (DECISIONS 2026-10-28). Only while the child is in, and only the
 * Check-in the tap was made on: the write itself says `undone_at IS NULL` and the revision, so an Undo at the door at
 * the same moment — or an Undo and a re-check-in that revived the row since (Astra, roll call 05) — wins and nothing
 * is marked (false).
 *
 * @extends BaseJob<bool>
 */
final class UpdateCheckInPresenceJob extends BaseJob
{
    public function __construct(
        private readonly CheckIn $checkIn,
        /** The revision the tap was made on (Who's in sends what it showed). */
        private readonly int $revision,
        private readonly bool $present,
        private readonly User $by,
    ) {}

    public function handle(): bool
    {
        $mark = $this->present
            ? ['present_at' => now(), 'present_by' => $this->by->id]
            : ['present_at' => null, 'present_by' => null];

        $written = CheckIn::query()
            ->where('church_id', $this->checkIn->church_id)
            ->whereKey($this->checkIn->id)
            ->where('revision', $this->revision)
            ->whereNull('undone_at')
            ->update($mark);
        if ($written === 0) {
            return false;
        }

        $this->checkIn->forceFill($mark)->syncOriginal();

        return true;
    }
}
