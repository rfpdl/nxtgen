<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\RegistrationSourceEnum;
use App\Foundation\Support\SearchWords;
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
     * Families matching a typed search (issue 04, SearchWords): EVERY word must hit the same family — a parent (see
     * scopeWithParentMatching) or a child's name or nickname — so "Ana Reyes" finds Ana's family, not every Reyes; and
     * a phone-shaped part must be a parent's phone. The ONE matching rule for the door search, staff registration and
     * the families list. Nothing left to match matches nothing.
     *
     * @param  Builder<Family>  $query
     */
    public function scopeMatching(Builder $query, string $term): void
    {
        SearchWords::of($term)->apply(
            $query,
            fn (Builder $one, string $word) => $one
                ->withParentMatching($word)
                ->orWhereHas('children', fn (Builder $c) => $c->where(fn (Builder $n) => $n
                    ->where('first_name', 'like', SearchWords::like($word))
                    ->orWhere('last_name', 'like', SearchWords::like($word))
                    ->orWhere('nickname', 'like', SearchWords::like($word)))),
            fn (Builder $one, string $digits) => $one->withParentPhone($digits),
        );
    }

    /**
     * Families with a parent who matches ONE search word: first or last name, email (theirs or their login's), or —
     * for a digit-ish word of four digits or more — phone. The children list asks this word by word, so "Janice" finds
     * Janice's children without a child's name dragging in their siblings (review 4).
     *
     * @param  Builder<Family>  $query
     */
    public function scopeWithParentMatching(Builder $query, string $word): void
    {
        $like = SearchWords::like($word);
        $digits = SearchWords::phoneDigitsOf($word);

        $query->whereHas('guardians', fn (Builder $g) => $g->where(fn (Builder $w) => $w
            ->where('first_name', 'like', $like)
            ->orWhere('last_name', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->when($digits !== null, fn (Builder $p) => $p->orWhere(fn (Builder $ph) => self::phoneHas($ph, (string) $digits)))
            ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', $like))));
    }

    /**
     * Families with a parent whose phone holds these digits.
     *
     * @param  Builder<Family>  $query
     */
    public function scopeWithParentPhone(Builder $query, string $digits): void
    {
        $query->whereHas('guardians', fn (Builder $g) => self::phoneHas($g, $digits));
    }

    /**
     * Phones are stored as typed ("+971 50 123 4567"), so compare digits with digits.
     *
     * @param  Builder<Guardian>  $guardian
     */
    private static function phoneHas(Builder $guardian, string $digits): void
    {
        $guardian->whereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ['%'.$digits.'%']);
    }
}
