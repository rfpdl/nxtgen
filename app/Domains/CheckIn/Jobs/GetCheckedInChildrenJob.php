<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;

/**
 * Who is in a Session right now: each Check-in not undone, newest first, with its child and Squad — the door's
 * checked-in list, each found child's ✓ and Undo, and its "N checked in".
 *
 * @extends BaseJob<list<CheckIn>>
 */
final class GetCheckedInChildrenJob extends BaseJob
{
    public function __construct(private readonly Session $session) {}

    /** @return list<CheckIn> */
    public function handle(): array
    {
        return array_values(CheckIn::query()
            ->where('church_id', $this->session->church_id)
            ->where('session_id', $this->session->id)
            ->whereNull('undone_at')
            ->with(['child', 'squad'])
            ->orderByDesc('checked_in_at')
            ->orderByDesc('id')
            ->get()
            ->all());
    }
}
