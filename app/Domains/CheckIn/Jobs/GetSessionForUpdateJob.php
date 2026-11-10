<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;

/**
 * The Session as it is NOW, its row locked until the caller's transaction ends — so a Check-in asking "still open?"
 * and an admin closing it are one before the other, never at once (spec item 15). Inside a transaction only.
 *
 * @extends BaseJob<Session>
 */
final class GetSessionForUpdateJob extends BaseJob
{
    public function __construct(private readonly Session $session) {}

    public function handle(): Session
    {
        return Session::query()->where('church_id', $this->session->church_id)->whereKey($this->session->id)->lockForUpdate()->firstOrFail();
    }
}
