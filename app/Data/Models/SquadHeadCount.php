<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SquadHeadCountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** How many children a Squad leader counted in a Squad at a Session (GLOSSARY "Roll call"). One per Squad per Session:
 * confirming again replaces it (latest wins).
 *
 * @property int $id
 * @property int $church_id
 * @property int $session_id
 * @property int $squad_id
 * @property int $head_count
 * @property CarbonImmutable $counted_at
 * @property int|null $counted_by
 * @property-read User|null $countedBy
 */
final class SquadHeadCount extends Model
{
    /** @use HasFactory<SquadHeadCountFactory> */
    use HasFactory;

    protected static function newFactory(): SquadHeadCountFactory
    {
        return SquadHeadCountFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'head_count' => 'integer',
            'counted_at' => 'immutable_datetime',
        ];
    }

    /**
     * The Staff member who confirmed the count (null if their login has since gone).
     *
     * @return BelongsTo<User, $this>
     */
    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
