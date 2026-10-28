<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\SquadHeadCount;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Confirm a Squad's head count at a Session (roll call, DECISIONS 2026-10-28), saying who and when. One per Squad per
 * Session, held by the unique (session, squad) key: the first confirm inserts it, a later one replaces it — latest
 * wins, no history.
 *
 * One atomic statement (MySQL INSERT … ON DUPLICATE KEY UPDATE): two leaders confirming at the same moment leave one
 * row, the write that lands last kept, with no exception to catch and no read to lock — so it is as safe inside a
 * caller's transaction as outside one.
 *
 * @extends BaseJob<SquadHeadCount>
 */
final class UpdateSquadHeadCountJob extends BaseJob
{
    public function __construct(
        private readonly Session $session,
        private readonly Squad $squad,
        private readonly int $count,
        private readonly User $by,
    ) {}

    public function handle(): SquadHeadCount
    {
        $church = $this->session->church_id;

        SquadHeadCount::query()->upsert(
            [['church_id' => $church, 'session_id' => $this->session->id, 'squad_id' => $this->squad->id, 'head_count' => $this->count, 'counted_at' => now(), 'counted_by' => $this->by->id]],
            ['session_id', 'squad_id'],
            ['head_count', 'counted_at', 'counted_by'],
        );

        return SquadHeadCount::query()->where('church_id', $church)->where('session_id', $this->session->id)->where('squad_id', $this->squad->id)->sole();
    }
}
