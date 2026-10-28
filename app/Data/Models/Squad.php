<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\GenderEnum;
use App\Foundation\Support\LevelChart;
use Database\Factories\SquadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A Satellite's split of a Level by gender, e.g. "Level 2 Boys" (null gender = mixed). Made from the chart in config;
 * the name is derived, never stored.
 *
 * @property int $id
 * @property int $church_id
 * @property int $satellite_id
 * @property int $level
 * @property GenderEnum|null $gender
 * @property-read Satellite $satellite
 */
final class Squad extends Model
{
    /** @use HasFactory<SquadFactory> */
    use HasFactory;

    protected static function newFactory(): SquadFactory
    {
        return SquadFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'gender' => GenderEnum::class,
        ];
    }

    /**
     * The one way a Satellite's Squads are made (ChurchSeeder, and SatelliteFactory::withSquads for tests): every
     * squad of the chart, keyed by (satellite, level, gender) — a rerun adds only what is missing.
     */
    public static function createChartFor(Satellite $satellite): void
    {
        foreach (LevelChart::squads() as $squad) {
            self::query()->firstOrCreate(['church_id' => $satellite->church_id, 'satellite_id' => $satellite->id] + $squad);
        }
    }

    /** "Level 2 Boys" — from the chart, so it cannot drift from it. */
    public function name(): string
    {
        return LevelChart::squadName($this->level, $this->gender);
    }

    /**
     * Where the Squad comes in a Session's list (Attendance, Who's in): by Level, then the mixed Squad, boys, girls —
     * named, not left to the enum's spelling.
     *
     * @return array{int, int}
     */
    public function rank(): array
    {
        return [$this->level, match ($this->gender) {
            null => 0,
            GenderEnum::Boy => 1,
            GenderEnum::Girl => 2,
        }];
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
