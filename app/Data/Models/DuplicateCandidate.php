<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\DuplicateReasonEnum;
use App\Enums\DuplicateStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** A possible duplicate for staff to review — never an automatic merge (plan §4.2).
 *
 * @property int $id
 * @property int $organization_id
 * @property string $subject_type
 * @property int $subject_id
 * @property int $candidate_id
 * @property DuplicateReasonEnum $reason
 * @property DuplicateStatusEnum $status
 * @property int|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
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
}
