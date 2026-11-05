<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\ChildStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One child of a Moving-Up: their Level and status before and after, and — once put back — who and when.
 *
 * @property int $id
 * @property int $church_id
 * @property int $moving_up_id
 * @property int $child_id
 * @property int $from_level
 * @property ChildStatusEnum $from_status
 * @property int $to_level
 * @property ChildStatusEnum $to_status
 * @property CarbonImmutable|null $put_back_at
 * @property int|null $put_back_by
 * @property-read Child $child
 * @property-read User|null $putBackBy
 */
final class MovingUpChild extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_level' => 'integer',
            'to_level' => 'integer',
            'from_status' => ChildStatusEnum::class,
            'to_status' => ChildStatusEnum::class,
            'put_back_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<MovingUp, $this> */
    public function movingUp(): BelongsTo
    {
        return $this->belongsTo(MovingUp::class);
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return BelongsTo<User, $this> */
    public function putBackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'put_back_by');
    }

    /** Still where Moving-Up took them — nobody has changed their Level or status since. */
    public function isWhereMovingUpLeft(Child $child): bool
    {
        return $child->level === $this->to_level && $child->status === $this->to_status;
    }
}
