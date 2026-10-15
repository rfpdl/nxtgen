<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\FamilyClaimStatusEnum;
use Carbon\CarbonImmutable;
use Database\Factories\FamilyClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A family waiting for an existing login to accept it (plan §4.2): registered with an email that already
 * belongs to a user with no family in this church. One pending claim per email per church (generated key).
 *
 * @property int $id
 * @property int $organization_id
 * @property int $family_id
 * @property int $guardian_id
 * @property string $email_normalised
 * @property int $revision
 * @property FamilyClaimStatusEnum $status
 * @property CarbonImmutable|null $resolved_at
 * @property-read Family $family
 * @property-read Guardian $guardian
 */
final class FamilyClaim extends Model
{
    /** @use HasFactory<FamilyClaimFactory> */
    use HasFactory;

    protected $guarded = ['pending_claim_key'];

    protected static function newFactory(): FamilyClaimFactory
    {
        return FamilyClaimFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => FamilyClaimStatusEnum::class,
            'revision' => 'integer',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<Guardian, $this> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
}
