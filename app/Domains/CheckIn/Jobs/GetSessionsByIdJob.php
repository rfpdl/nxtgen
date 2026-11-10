<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;

/**
 * The Sessions these ids name, within this church only, with their class and satellite, keyed by id — a helper for the
 * day's Role is at one Session (spec item 18): People names it, and asks who may give or remove it.
 *
 * @extends BaseJob<array<int, Session>>
 */
final class GetSessionsByIdJob extends BaseJob
{
    /** @param list<int> $ids */
    public function __construct(
        private readonly Church $church,
        private readonly array $ids,
    ) {}

    /** @return array<int, Session> */
    public function handle(): array
    {
        if ($this->ids === []) {
            return [];
        }

        $sessions = [];
        foreach (Session::query()->where('church_id', $this->church->id)->whereIn('id', $this->ids)->with(['schedule', 'satellite'])->get() as $session) {
            $sessions[$session->id] = $session;
        }

        return $sessions;
    }
}
