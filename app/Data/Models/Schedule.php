<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\WeekdayEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A Satellite's named kids class (GLOSSARY "Schedule"; shown as "class"; spec item 14): weekly — "1PM" on Saturdays —
 * or one-time on one date — "Christmas party". Always a start and an end time. A class is retired (is_active off),
 * never deleted.
 *
 * @property int $id
 * @property int $church_id
 * @property int $satellite_id
 * @property string $name
 * @property WeekdayEnum|null $weekday
 * @property CarbonImmutable|null $date
 * @property string $starts_at
 * @property string $ends_at
 * @property bool $is_active
 * @property-read Satellite $satellite
 */
final class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    protected static function newFactory(): ScheduleFactory
    {
        return ScheduleFactory::new();
    }

    protected $guarded = ['active_name'];

    protected function casts(): array
    {
        return [
            'weekday' => WeekdayEnum::class,
            'date' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }

    /** A weekly class meets every week on its weekday; a one-time class on its date only (spec item 14). */
    public function meetsOn(CarbonInterface $date): bool
    {
        return $this->date === null
            ? $this->weekday === WeekdayEnum::of($date)
            : $this->date->isSameDay($date);
    }

    /** A one-time class whose date has gone (Roy 2026-11-10: it moves to Classes' "Past" list); a weekly class never is. */
    public function isPastOn(CarbonInterface $today): bool
    {
        return $this->date !== null && $this->date->toDateString() < $today->toDateString();
    }

    /** "13:00" — the stored time without seconds. */
    public function startsAt(): string
    {
        return mb_substr($this->starts_at, 0, 5);
    }

    public function endsAt(): string
    {
        return mb_substr($this->ends_at, 0, 5);
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
}
