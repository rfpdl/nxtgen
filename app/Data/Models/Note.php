<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\NoteKindEnum;
use App\Enums\NoteVisibilityEnum;
use Carbon\CarbonImmutable;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A note on a family or a child (plan §5): `system` notes are written by the app when staff change something
 * (who, what, when); `observation` notes are a Lifeshaper's (Release 2). `noteable_type` is a morph-map alias
 * (`family`, `child`), never a class name.
 *
 * @property int $id
 * @property int $church_id
 * @property string $noteable_type
 * @property int $noteable_id
 * @property int|null $author_id
 * @property NoteKindEnum $kind
 * @property string $body
 * @property NoteVisibilityEnum $visibility
 * @property CarbonImmutable|null $created_at
 * @property-read User|null $author
 */
final class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    protected static function newFactory(): NoteFactory
    {
        return NoteFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kind' => NoteKindEnum::class,
            'visibility' => NoteVisibilityEnum::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    /** A staff-only system note about a family or a child — the ONE way the app writes one. */
    public static function system(Family|Child $about, string $body, ?User $by): self
    {
        return self::query()->create([
            'church_id' => $about->church_id,
            'noteable_type' => $about->getMorphClass(),
            'noteable_id' => $about->id,
            'author_id' => $by?->id,
            'kind' => NoteKindEnum::System,
            'body' => $body,
            'visibility' => NoteVisibilityEnum::Staff,
        ]);
    }

    /** @return MorphTo<Model, $this> */
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
