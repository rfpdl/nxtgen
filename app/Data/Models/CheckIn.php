<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CheckInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A Child at a Session, recorded by Staff at the door, in this Satellite's Squad for the child's Level and gender
 * (GLOSSARY "Check-in"). One per child per Session: an Undo stamps `undone_at`, and checking in again revives the row.
 *
 * @property int $id
 * @property int $church_id
 * @property int $session_id
 * @property int $child_id
 * @property int $squad_id
 * @property CarbonImmutable $checked_in_at
 * @property int|null $checked_in_by
 * @property CarbonImmutable|null $undone_at
 * @property int|null $undone_by
 * @property-read Session $session
 * @property-read Child $child
 * @property-read Squad $squad
 */
final class CheckIn extends Model
{
    /** @use HasFactory<CheckInFactory> */
    use HasFactory;

    protected static function newFactory(): CheckInFactory
    {
        return CheckInFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'immutable_datetime',
            'undone_at' => 'immutable_datetime',
        ];
    }

    /** The child is in: checked in and not undone. */
    public function isIn(): bool
    {
        return $this->undone_at === null;
    }

    /** @return BelongsTo<Session, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return BelongsTo<Squad, $this> */
    public function squad(): BelongsTo
    {
        return $this->belongsTo(Squad::class);
    }
}
