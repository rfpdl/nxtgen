<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;

/**
 * A closed Session opened again the same day (spec item 15) — the same Session, so its Check-ins and (issue 12) its
 * address stay. Who first opened it stays too.
 *
 * @extends BaseJob<Session>
 */
final class UpdateSessionAsOpenJob extends BaseJob
{
    public function __construct(private readonly Session $session) {}

    public function handle(): Session
    {
        $this->session->update(['closed_at' => null, 'closed_by' => null]);

        return $this->session;
    }
}
