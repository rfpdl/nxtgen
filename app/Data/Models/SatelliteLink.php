<?php

declare(strict_types=1);

namespace App\Data\Models;

use Database\Factories\SatelliteLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Link (GLOSSARY): one of a satellite's own addresses on its QR poster — a label and an https address, in the
 * order the admin put them.
 *
 * @property int $id
 * @property int $church_id
 * @property int $satellite_id
 * @property string $label
 * @property string $url
 * @property int $position
 * @property-read Satellite $satellite
 */
final class SatelliteLink extends Model
{
    /** @use HasFactory<SatelliteLinkFactory> */
    use HasFactory;

    /** A satellite's poster holds this many, so it prints on one page. */
    public const int MAX_PER_SATELLITE = 6;

    protected static function newFactory(): SatelliteLinkFactory
    {
        return SatelliteLinkFactory::new();
    }

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @return BelongsTo<Satellite, $this> */
    public function satellite(): BelongsTo
    {
        return $this->belongsTo(Satellite::class);
    }
}
