<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OrganizationFactory;
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
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'moving_up_reference_date' => 'date',
            'settings' => 'array',
        ];
    }

    /** @return HasMany<Branch, $this> */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
