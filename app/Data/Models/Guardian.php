<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\RelationshipEnum;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An adult in a family. May have a login (`user_id`); grandparents usually don't.
 *
 * @property int $id
 * @property int $church_id
 * @property int $family_id
 * @property int|null $user_id
 * @property RelationshipEnum $relationship
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string|null $email
 * @property list<string>|null $ministries
 * @property bool $can_pick_up
 * @property-read Family $family
 * @property-read User|null $user
 */
final class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use HasFactory;

    protected static function newFactory(): GuardianFactory
    {
        return GuardianFactory::new();
    }

    protected $guarded = ['login_family_key'];

    protected function casts(): array
    {
        return [
            'relationship' => RelationshipEnum::class,
            'ministries' => 'array',
            'can_pick_up' => 'boolean',
        ];
    }

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
