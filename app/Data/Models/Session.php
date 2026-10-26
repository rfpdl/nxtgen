<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One class (Schedule) on one date, e.g. Saturday 31 Oct, 1PM at CCF Dxb (GLOSSARY "Session"). Made by its first
 * Check-in; never opened or closed. The table is `schedule_sessions` — Laravel's session driver owns `sessions`.
 *
 * @property int $id
 * @property int $church_id
 * @property int $satellite_id
 * @property int $schedule_id
 * @property CarbonImmutable $date
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
        ];
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
