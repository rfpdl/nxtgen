<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Support\LevelChart;
use App\Foundation\Support\NameKey;
use Carbon\CarbonImmutable;
use Database\Factories\ChildFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A child. Belongs to a family and stores its Level; the nickname is the name everyone uses.
 *
 * @property int $id
 * @property int $church_id
 * @property int $family_id
 * @property int|null $level
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string $nickname
 * @property GenderEnum $gender
 * @property CarbonImmutable $date_of_birth
 * @property string|null $allergies
 * @property string $name_key
 * @property string $name_key_first
 * @property string $name_key_last
 * @property string|null $illnesses
 * @property string|null $dislikes
 * @property string|null $fears
 * @property string|null $guardian_notes
 * @property ChildStatusEnum $status
 * @property CarbonImmutable|null $status_changed_at
 * @property int|null $status_changed_by
 * @property CarbonImmutable|null $level_set_at
 * @property int|null $level_set_by
 * @property string|null $photo_path
 * @property CarbonImmutable|null $photo_consent_at
 * @property int|null $photo_consent_by
 * @property RegistrationSourceEnum $registration_source
 * @property-read Family $family
 */
final class Child extends Model
{
    /** @use HasFactory<ChildFactory> */
    use HasFactory;

    protected static function newFactory(): ChildFactory
    {
        return ChildFactory::new();
    }

    protected $table = 'children';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gender' => GenderEnum::class,
            'date_of_birth' => 'immutable_date',
            'status' => ChildStatusEnum::class,
            'status_changed_at' => 'immutable_datetime',
            'level' => 'integer',
            'level_set_at' => 'immutable_datetime',
            'photo_consent_at' => 'immutable_datetime',
            'registration_source' => RegistrationSourceEnum::class,
        ];
    }

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * The name-card keys (slice 3, NameKey) follow the names on EVERY save — registration, edits by a parent or staff,
     * the registry import, seeders — so the door's card scan always matches the names as they are now. A save that
     * changes any part of the name writes all of it, with its keys.
     */
    protected static function booted(): void
    {
        self::saving(function (Child $child): void {
            $parts = ['first_name', 'middle_name', 'last_name'];
            if ($child->exists && ! $child->isDirty($parts)) {
                return;
            }
            // Write the WHOLE name with its keys, not only the part that changed: Eloquent saves dirty columns only, so
            // two edits of different parts at once would otherwise store one name with the other's keys (Astra). The
            // later edit wins the whole name.
            $keys = NameKey::keysFor($child->first_name, $child->middle_name, $child->last_name);
            foreach ([...$parts, ...array_keys($keys)] as $column) {
                unset($child->original[$column]);
            }
            $child->forceFill($keys);
        });
    }

    public function displayName(): string
    {
        return $this->nickname !== '' ? $this->nickname : $this->first_name;
    }

    /** Something is written under allergies — the ⚠ flag on the door's and Who's in's lists (never the detail). */
    public function hasAllergies(): bool
    {
        return $this->allergies !== null && trim($this->allergies) !== '';
    }

    /** "Amara Cruz": first and last name, no middle — for a tight row (Who's in, issue 04). */
    public function shortName(): string
    {
        return trim((string) preg_replace('/\s+/', ' ', "{$this->first_name} {$this->last_name}"));
    }

    /** "Amara Joy Cruz": first, middle and last name, spaces collapsed — the one way lists and Excel write it. */
    public function fullName(): string
    {
        return trim((string) preg_replace('/\s+/', ' ', "{$this->first_name} {$this->middle_name} {$this->last_name}"));
    }

    /** "Level 2", or null while the child has no Level yet. */
    public function levelName(): ?string
    {
        return $this->level === null ? null : LevelChart::name($this->level);
    }
}
