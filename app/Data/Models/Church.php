<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ChurchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A church — the tenant.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $timezone
 * @property string $currency
 * @property CarbonImmutable|null $moving_up_reference_date
 * @property array<string, mixed>|null $settings
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class Church extends Model
{
    /** @use HasFactory<ChurchFactory> */
    use HasFactory;

    protected static function newFactory(): ChurchFactory
    {
        return ChurchFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'moving_up_reference_date' => 'date',
            'settings' => 'array',
        ];
    }

    /** @return HasMany<Satellite, $this> */
    public function satellites(): HasMany
    {
        return $this->hasMany(Satellite::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
