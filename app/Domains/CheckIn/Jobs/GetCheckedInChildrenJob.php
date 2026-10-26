<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;

/**
 * Who is in a Session right now: each checked-in child's Check-in id, keyed by child id (undone ones left out) — the
 * door's ✓ and Undo, and, counted, its "N checked in".
 *
 * @extends BaseJob<array<int, int>>
 */
final class GetCheckedInChildrenJob extends BaseJob
{
    public function __construct(private readonly Session $session) {}

    /** @return array<int, int> child id => check-in id */
    public function handle(): array
    {
        /** @var array<int, int> $in */
        $in = CheckIn::query()
            ->where('church_id', $this->session->church_id)
            ->where('session_id', $this->session->id)
            ->whereNull('undone_at')
            ->orderBy('id')
            ->pluck('id', 'child_id')
            ->all();

        return $in;
    }
}
