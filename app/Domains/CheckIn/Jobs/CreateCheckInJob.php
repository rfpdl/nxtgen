<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Enums\CheckInResultEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Check a child in to a Session, in `$squad` (this satellite's, for the child's Level and gender), saying who and
 * when. One Check-in per child per Session, held by the unique (session, child) key:
 *
 * - the first press inserts the row → CheckedIn;
 * - a press on a child who is in (another phone got there first, even at the same moment) → AlreadyIn, row untouched;
 * - a press after an Undo revives the same row with the new who, when and Squad → CheckedIn.
 *
 * When the insert meets the key, the row is read with a LOCKING read: inside the caller's transaction (MySQL
 * REPEATABLE READ) a plain read sees only its own snapshot, which cannot hold the other phone's new row. The locking
 * read waits for that phone to commit and sees the row; holding the lock, the revive is decided once. The caller
 * decides who may be checked in (Graduated, inactive, no Level) before running this.
 *
 * @extends BaseJob<CheckInResultEnum>
 */
final class CreateCheckInJob extends BaseJob
{
    public function __construct(
        private readonly Session $session,
        private readonly Child $child,
        private readonly Squad $squad,
        private readonly User $by,
    ) {}

    public function handle(): CheckInResultEnum
    {
        $church = $this->session->church_id;
        $in = ['squad_id' => $this->squad->id, 'checked_in_at' => now(), 'checked_in_by' => $this->by->id];

        try {
            CheckIn::query()->withSavepointIfNeeded(fn () => CheckIn::query()->create(['church_id' => $church, 'session_id' => $this->session->id, 'child_id' => $this->child->id] + $in));

            return CheckInResultEnum::CheckedIn;
        } catch (UniqueConstraintViolationException) {
            $checkIn = CheckIn::query()->where('church_id', $church)->where('session_id', $this->session->id)->where('child_id', $this->child->id)->lockForUpdate()->firstOrFail();
        }

        if ($checkIn->isIn()) {
            return CheckInResultEnum::AlreadyIn;
        }
        $checkIn->forceFill($in + ['undone_at' => null, 'undone_by' => null])->save();

        return CheckInResultEnum::CheckedIn;
    }
}
