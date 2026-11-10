<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Session;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * An admin closes a Session (spec item 15): from now on it can be looked at, not changed (Roy 2026-11-10). It can be
 * opened again the same day (UpdateSessionAsOpenJob).
 *
 * @extends BaseJob<Session>
 */
final class UpdateSessionAsClosedJob extends BaseJob
{
    public function __construct(
        private readonly Session $session,
        private readonly ?User $by,
    ) {}

    public function handle(): Session
    {
        $this->session->update(['closed_at' => now(), 'closed_by' => $this->by?->id]);

        return $this->session;
    }
}
