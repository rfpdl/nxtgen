<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Database\Factories\RoleAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A staff role in one scope: the whole church, one satellite, or one class (plan §3.2).
 *
 * @property int $id
 * @property int $church_id
 * @property int $user_id
 * @property RoleEnum $role
 * @property ScopeTypeEnum $scope_type
 * @property int $scope_id
 * @property bool $is_active
 * @property int|null $granted_by
 * @property-read User $user
 */
final class RoleAssignment extends Model
{
    /** @use HasFactory<RoleAssignmentFactory> */
    use HasFactory;

    protected static function newFactory(): RoleAssignmentFactory
    {
        return RoleAssignmentFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => RoleEnum::class,
            'scope_type' => ScopeTypeEnum::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
