<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * A Squad by id (from the address), only when a child is checked in to it at this Session (not undone) — the Squads a
 * head count can be confirmed for. Anything else is not found (a 404).
 *
 * @extends BaseJob<Squad>
 */
final class GetSessionSquadJob extends BaseJob
{
    public function __construct(
        private readonly Session $session,
        private readonly int $id,
    ) {}

    public function handle(): Squad
    {
        $in = CheckIn::query()
            ->where('church_id', $this->session->church_id)
            ->where('session_id', $this->session->id)
            ->where('squad_id', $this->id)
            ->whereNull('undone_at')
            ->with('squad')
            ->first();

        return $in->squad ?? throw (new ModelNotFoundException)->setModel(Squad::class, [$this->id]);
    }
}
