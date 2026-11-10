<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\SessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One class (Schedule) on one date, e.g. Saturday 31 Oct, 1PM at CCF Dubai (GLOSSARY "Session"). Made when an admin
 * opens the class (spec item 15); open until closed, or until midnight at the satellite. Its `code` is its address
 * (`/<code>/checkin`, spec item 16). The table is
 * `schedule_sessions` — Laravel's session driver owns `sessions`.
 *
 * @property int $id
 * @property int $church_id
 * @property int $satellite_id
 * @property int $schedule_id
 * @property CarbonImmutable $date
 * @property string $code
 * @property CarbonImmutable $opened_at
 * @property int|null $opened_by
 * @property CarbonImmutable|null $closed_at
 * @property int|null $closed_by
 * @property-read Church $church
 * @property-read Schedule $schedule
 * @property-read Satellite $satellite
 */
final class Session extends Model
{
    /** @use HasFactory<SessionFactory> */
    use HasFactory;

    protected static function newFactory(): SessionFactory
    {
        return SessionFactory::new();
    }

    protected $table = 'schedule_sessions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /**
     * Open on `$today` (the church's date): its own date, and not closed. A Session of another day is closed — at
     * midnight at the satellite, with no job needed (spec item 15).
     */
    public function isOpenOn(CarbonInterface $today): bool
    {
        return $this->date->isSameDay($today) && $this->closed_at === null;
    }

    /** Its address names it by its code, never its id (spec item 16): `route('session.checkin', $session)`. */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /** @return BelongsTo<Satellite, $this> */
    public function satellite(): BelongsTo
    {
        return $this->belongsTo(Satellite::class);
    }

    /** @return BelongsTo<Schedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
