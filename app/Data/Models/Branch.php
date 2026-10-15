<?php

declare(strict_types=1);

namespace App\Data\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A satellite: a place where the church meets, with its own classes.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $slug
 * @property-read Organization $organization
 */
final class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }

    protected $guarded = [];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
