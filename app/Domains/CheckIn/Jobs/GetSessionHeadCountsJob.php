<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Session;
use App\Data\Models\SquadHeadCount;
use App\Foundation\Jobs\BaseJob;

/**
 * Each Squad's confirmed head count at a Session, by Squad id, with its Squad and who confirmed it — the same few
 * queries however many Squads.
 *
 * @extends BaseJob<array<int, SquadHeadCount>>
 */
final class GetSessionHeadCountsJob extends BaseJob
{
    public function __construct(private readonly Session $session) {}

    /** @return array<int, SquadHeadCount> */
    public function handle(): array
    {
        $counts = [];
        foreach (SquadHeadCount::query()
            ->where('church_id', $this->session->church_id)
            ->where('session_id', $this->session->id)
            ->with(['squad', 'countedBy'])
            ->get() as $count) {
            $counts[$count->squad_id] = $count;
        }

        return $counts;
    }
}
