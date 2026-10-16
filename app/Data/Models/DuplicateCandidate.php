<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\DuplicateReasonEnum;
use App\Enums\DuplicateStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** A possible duplicate for staff to review — never an automatic merge (plan §4.2).
 *
 * @property int $id
 * @property int $church_id
 * @property string $subject_type
 * @property int $subject_id
 * @property int $candidate_id
 * @property DuplicateReasonEnum $reason
 * @property DuplicateStatusEnum $status
 * @property int|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
 * @property-read Family|Child $subject the NEW record (a family, or a child of the new family)
 * @property-read Family|Child $candidate the existing record it may duplicate (same kind as the subject)
 */
final class DuplicateCandidate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reason' => DuplicateReasonEnum::class,
            'status' => DuplicateStatusEnum::class,
            'resolved_at' => 'immutable_datetime',
        ];
    }

    /** @return MorphTo<Model, $this> `subject_type` holds the morph alias (`family` | `child`). */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    /** @return MorphTo<Model, $this> Same kind as the subject: the pair shares `subject_type`. */
    public function candidate(): MorphTo
    {
        return $this->morphTo('candidate', 'subject_type', 'candidate_id');
    }

    /** The family on each side — a child pair is reviewed as the two children's families. */
    public static function familyOf(Family|Child $record): Family
    {
        return $record instanceof Child ? $record->family : $record;
    }
}
