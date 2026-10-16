<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\RegistrationSourceEnum;
use Carbon\CarbonImmutable;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A household: guardians + children. Belongs to the church; attends a home satellite.
 *
 * @property int $id
 * @property int $church_id
 * @property int $home_satellite_id
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
 * @property list<int>|null $preferred_schedule_ids
 * @property CarbonImmutable|null $privacy_consented_at
 * @property int|null $registered_by_user_id
 * @property RegistrationSourceEnum $registration_source
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property-read Church $church
 * @property-read Satellite $homeSatellite
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
            'preferred_schedule_ids' => 'array',
            'privacy_consented_at' => 'immutable_datetime',
            'registration_source' => RegistrationSourceEnum::class,
        ];
    }

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /** @return BelongsTo<Satellite, $this> */
    public function homeSatellite(): BelongsTo
    {
        return $this->belongsTo(Satellite::class, 'home_satellite_id');
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

    /**
     * Families matching a typed search: a parent's contact (see scopeMatchingContact), or a child's name or
     * nickname. The ONE matching rule for the door search and the families list. Under two characters matches
     * nothing.
     *
     * @param  Builder<Family>  $query
     */
    public function scopeMatching(Builder $query, string $term): void
    {
        $like = self::like($term);
        if ($like === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(fn (Builder $q) => $q
            ->matchingContact($term)
            ->orWhereHas('children', fn (Builder $c) => $c
                ->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('nickname', 'like', $like)));
    }

    /**
     * Families whose PARENT matches: name, email (login or guardian) or phone digits. Also used by the children
     * list, so "Janice" finds Janice's children without a child's name dragging in their siblings (review 4).
     *
     * @param  Builder<Family>  $query
     */
    public function scopeMatchingContact(Builder $query, string $term): void
    {
        $like = self::like($term);
        if ($like === null) {
            $query->whereRaw('1 = 0');

            return;
        }
        $digits = (string) preg_replace('/\D+/', '', $term);

        $query->whereHas('guardians', fn (Builder $g) => $g
            ->where(fn (Builder $w) => $w
                ->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                // Phones are stored as typed ("+971 50 123 4567"), so compare digits with digits.
                ->when(strlen($digits) >= 4, fn (Builder $p) => $p->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ['%'.$digits.'%']))
                ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', $like))));
    }

    private static function like(string $term): ?string
    {
        $term = trim($term);

        return mb_strlen($term) < 2 ? null : '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
    }
}
