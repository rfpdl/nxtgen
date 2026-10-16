<?php

declare(strict_types=1);

namespace App\Data\Models;

use Database\Factories\SatelliteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A satellite: a place where the church meets, with its own classes.
 *
 * @property int $id
 * @property int $church_id
 * @property string $name
 * @property string $slug
 * @property-read Church $church
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
}
