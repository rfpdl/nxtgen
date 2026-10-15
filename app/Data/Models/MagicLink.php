<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\MagicLinkPurposeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A single-use sign-in, confirm-email or claim-family link (plan §4.3). Only the token's hash is stored.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $email_snapshot
 * @property MagicLinkPurposeEnum $purpose
 * @property int|null $family_claim_id
 * @property int|null $claim_revision
 * @property int|null $credential_version
 * @property string $token_hash
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $used_at
 * @property-read User|null $user
 * @property-read FamilyClaim|null $familyClaim
 */
final class MagicLink extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'purpose' => MagicLinkPurposeEnum::class,
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
            'claim_revision' => 'integer',
            'credential_version' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<FamilyClaim, $this> */
    public function familyClaim(): BelongsTo
    {
        return $this->belongsTo(FamilyClaim::class);
    }
}
