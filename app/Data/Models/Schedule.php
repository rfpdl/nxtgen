<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\WeekdayEnum;
use Carbon\CarbonInterface;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A Satellite's named, regular kids class, e.g. "1PM" on Saturdays (GLOSSARY "Schedule"; shown as "class"). The day
 * and times are optional; a class is retired (is_active off), never deleted.
 *
 * @property int $id
 * @property int $church_id
 * @property int $satellite_id
 * @property string $name
 * @property WeekdayEnum|null $weekday
 * @property string|null $starts_at
 * @property string|null $ends_at
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
            'is_active' => 'boolean',
        ];
    }

    /** A class with a day meets only on that day; one with no day meets every day (DECISIONS 2026-10-26). */
    public function meetsOn(CarbonInterface $date): bool
    {
        return $this->weekday === null || $this->weekday === WeekdayEnum::of($date);
    }

    /** "13:00" — the stored time without seconds; null when the class has none. */
    public function startsAt(): ?string
    {
        return self::hhmm($this->starts_at);
    }

    public function endsAt(): ?string
    {
        return self::hhmm($this->ends_at);
    }

    private static function hhmm(?string $time): ?string
    {
        return $time === null ? null : mb_substr($time, 0, 5);
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
