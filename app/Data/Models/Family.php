<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\RegistrationSourceEnum;
use Carbon\CarbonImmutable;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A household: guardians + children. Belongs to the church; attends a home satellite.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $home_branch_id
 * @property int|null $primary_guardian_id
 * @property string|null $area_text
 * @property string|null $fb_account
 * @property bool $wants_fb_group
 * @property bool $in_dgroup
 * @property string|null $dgroup_area_text
 * @property string|null $dgroup_leader_name
 * @property string|null $dgroup_leader_phone
 * @property bool $wants_dgroup
 * @property string|null $lifestage
 * @property list<int>|null $preferred_event_series_ids
 * @property CarbonImmutable|null $privacy_consented_at
 * @property int|null $registered_by_user_id
 * @property RegistrationSourceEnum $registration_source
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property-read Organization $organization
 * @property-read Branch $homeBranch
 * @property-read Guardian|null $primaryGuardian
 * @property-read Collection<int, Guardian> $guardians
 * @property-read Collection<int, Child> $children
 */
final class Family extends Model
{
    /** @use HasFactory<FamilyFactory> */
    use HasFactory;

    protected static function newFactory(): FamilyFactory
    {
        return FamilyFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'wants_fb_group' => 'boolean',
            'in_dgroup' => 'boolean',
            'wants_dgroup' => 'boolean',
            'preferred_event_series_ids' => 'array',
            'privacy_consented_at' => 'immutable_datetime',
            'registration_source' => RegistrationSourceEnum::class,
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function homeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    /** @return HasMany<Guardian, $this> */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    /** @return HasMany<Child, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    /** @return BelongsTo<Guardian, $this> */
    public function primaryGuardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'primary_guardian_id');
    }

    /*
     * The family's contact, derived in ONE place (was copied five times — review-spar). The primary guardian's
     * login email, or the guardian's own email while a claim is pending. Read-only; needs primaryGuardian.user loaded.
     */
    public function contactName(): string
    {
        $g = $this->primaryGuardian;

        return $g !== null ? "{$g->first_name} {$g->last_name}" : '—';
    }

    public function contactEmail(): string
    {
        $g = $this->primaryGuardian;

        return $g?->user->email ?? $g->email ?? '';
    }

    public function contactEmailConfirmed(): bool
    {
        return $this->primaryGuardian?->user?->email_verified_at !== null;
    }
}
