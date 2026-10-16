<?php

declare(strict_types=1);

namespace App\Data\Models;

use Database\Factories\SatelliteFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A satellite: a place where the church meets, with its own Squads and Schedules.
 *
 * @property int $id
 * @property int $church_id
 * @property string $name
 * @property string $slug
 * @property-read Church $church
 * @property-read Collection<int, Squad> $squads
 */
final class Satellite extends Model
{
    /** @use HasFactory<SatelliteFactory> */
    use HasFactory;

    protected static function newFactory(): SatelliteFactory
    {
        return SatelliteFactory::new();
    }

    protected $guarded = [];

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /** @return HasMany<Squad, $this> */
    public function squads(): HasMany
    {
        return $this->hasMany(Squad::class);
    }
}
