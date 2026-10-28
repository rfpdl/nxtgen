<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Undo a Check-in (GLOSSARY "Check-in": a Check-in can be undone): the row stays, stamped with who undid it and
 * when, and the child is no longer in — and no longer marked present (roll call, DECISIONS 2026-10-28). Checking
 * them in again revives the same row (CreateCheckInJob).
 *
 * @extends BaseJob<CheckIn>
 */
final class UpdateCheckInAsUndoneJob extends BaseJob
{
    public function __construct(
        private readonly CheckIn $checkIn,
        private readonly User $by,
    ) {}

    public function handle(): CheckIn
    {
        $undone = ['undone_at' => now(), 'undone_by' => $this->by->id, 'present_at' => null, 'present_by' => null];
        // One UPDATE that always writes all four: saving the loaded model would skip present_at/by when they were
        // null when loaded, and keep a mark another request made in between (Astra, roll call 05).
        CheckIn::query()->where('church_id', $this->checkIn->church_id)->whereKey($this->checkIn->id)->update($undone);
        $this->checkIn->forceFill($undone)->syncOriginal();

        return $this->checkIn;
    }
}
