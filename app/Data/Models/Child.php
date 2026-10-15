<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;
use Carbon\CarbonImmutable;
use Database\Factories\ChildFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A child. Belongs to a family; the nickname is the name everyone uses.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $family_id
 * @property int|null $group_id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string $nickname
 * @property GenderEnum $gender
 * @property CarbonImmutable $date_of_birth
 * @property string|null $allergies
 * @property string|null $illnesses
 * @property string|null $dislikes
 * @property string|null $fears
 * @property string|null $parent_notes
 * @property ChildStatusEnum $status
 * @property CarbonImmutable|null $status_changed_at
 * @property int|null $status_changed_by
 * @property CarbonImmutable|null $group_set_at
 * @property int|null $group_set_by
 * @property string|null $photo_path
 * @property CarbonImmutable|null $photo_consent_at
 * @property int|null $photo_consent_by
 * @property RegistrationSourceEnum $registration_source
 * @property-read Family $family
 * @property-read Group|null $group
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
            'group_set_at' => 'immutable_datetime',
            'photo_consent_at' => 'immutable_datetime',
            'registration_source' => RegistrationSourceEnum::class,
        ];
    }

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function displayName(): string
    {
        return $this->nickname !== '' ? $this->nickname : $this->first_name;
    }
}
