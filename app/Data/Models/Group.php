<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\GenderEnum;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A class group by age and gender, e.g. "Level 2 Boys". Changes only at Moving-Up.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property int $sort
 * @property int $min_age
 * @property int $max_age
 * @property GenderEnum|null $gender
 * @property int|null $next_group_id
 * @property bool $is_active
 * @property-read Group|null $nextGroup
 */
final class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected static function newFactory(): GroupFactory
    {
        return GroupFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gender' => GenderEnum::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Group, $this> */
    public function nextGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'next_group_id');
    }
}
