<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Database\Factories\SatelliteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A satellite: a place where the church meets, with its own Squads and Schedules.
 *
 * @property int $id
 * @property int $church_id
 * @property string $name
 * @property string $slug
 * @property string $code the registration code its QR poster carries: never changes (spec item 7)
 * @property-read Church $church
 * @property-read Collection<int, Squad> $squads
 */
final class Satellite extends Model
{
    /** @use HasFactory<SatelliteFactory> */
    use HasFactory;

    protected static function newFactory(): SatelliteFactory
    {
        return SatelliteFactory::new();
    }

    protected $guarded = [];

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * The satellites `$user` may open (spec items 9–12) — the ONE answer for where they land and what the bar's
     * switch offers: those an active Role covers (at the satellite, or at one of its active classes); every satellite
     * for the platform admin. AccessScope still decides what they may do there.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpenTo(Builder $query, User $user): void
    {
        if ($user->is_platform_admin) {
            return;
        }

        // Exactly GetAccessScopeJob's rule (a parity test holds them together): an active Role at the satellite, the
        // Admin Role at one of its active classes, or a helper's Session open there today — each row in the satellite's
        // own church.
        $roles = fn (ScopeTypeEnum $type): Builder => RoleAssignment::query()
            ->where('user_id', $user->id)->where('is_active', true)->where('scope_type', $type)
            ->whereColumn('role_assignments.church_id', 'satellites.church_id');
        $query->where(fn (Builder $q) => $q
            ->whereExists($roles(ScopeTypeEnum::Satellite)->whereColumn('role_assignments.scope_id', 'satellites.id')
                ->whereIn('role_assignments.role', [RoleEnum::Admin, RoleEnum::Lifeshaper]))
            ->orWhereExists($roles(ScopeTypeEnum::Schedule)->where('role_assignments.role', RoleEnum::Admin)
                ->whereExists(Schedule::query()->whereColumn('schedules.id', 'role_assignments.scope_id')
                    ->whereColumn('schedules.satellite_id', 'satellites.id')
                    ->whereColumn('schedules.church_id', 'satellites.church_id')
                    ->where('schedules.is_active', true)))
            // A helper for the day (spec item 18), while their Session there is open: its date the church's today.
            ->orWhereExists($roles(ScopeTypeEnum::Session)->where('role_assignments.role', RoleEnum::Helper)
                ->whereExists(Session::query()->whereColumn('schedule_sessions.id', 'role_assignments.scope_id')
                    ->whereColumn('schedule_sessions.satellite_id', 'satellites.id')
                    ->whereColumn('schedule_sessions.church_id', 'satellites.church_id')
                    ->whereNull('schedule_sessions.closed_at')
                    ->whereExists(Schedule::query()->whereColumn('schedules.id', 'schedule_sessions.schedule_id')->where('schedules.is_active', true))
                    // Each church's own today, for its own Sessions only (Astra): another church's clock never counts.
                    ->where(function (Builder $today): void {
                        foreach (Church::query()->get(['id', 'timezone']) as $church) {
                            $today->orWhere(fn (Builder $its) => $its
                                ->where('schedule_sessions.church_id', $church->id)
                                ->whereDate('schedule_sessions.date', $church->today()->toDateString()));
                        }
                    }))));
    }

    /** Its address is its slug (`/dubai/…`, Roy 2026-11-07), so a link built from the model says `dubai`. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<Squad, $this> */
    public function squads(): HasMany
    {
        return $this->hasMany(Squad::class);
    }
}
