<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One Moving-Up (GLOSSARY): the children a satellite admin moved to the next Level, exactly those, kept when it ran — so
 * the last one can be put back.
 *
 * @property int $id
 * @property int $church_id
 * @property int|null $made_by
 * @property CarbonImmutable $created_at
 * @property-read User|null $maker
 * @property-read Collection<int, MovingUpChild> $children
 */
final class MovingUp extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * The children it moved, each with where they were and where they went.
     *
     * @return HasMany<MovingUpChild, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MovingUpChild::class);
    }

    /**
     * The admin who ran it (null if their login has since gone).
     *
     * @return BelongsTo<User, $this>
     */
    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'made_by');
    }
}
