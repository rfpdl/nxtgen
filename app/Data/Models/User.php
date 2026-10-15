<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A login, nothing else (plan §4). Parent access comes from `guardians()`, staff access from
 * `roleAssignments()`; one person can have both, with one email.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property int $credential_version
 * @property string|null $password
 * @property bool $is_platform_admin
 * @property CarbonImmutable|null $last_login_at
 * @property-read Collection<int, Guardian> $guardians
 * @property-read Collection<int, RoleAssignment> $roleAssignments
 */
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $guarded = ['is_platform_admin'];

    /**
     * Mirrors the column default so a freshly created model carries it; otherwise a link issued right after
     * creation snapshots null and can never be redeemed.
     *
     * @var array<string, mixed>
     */
    protected $attributes = ['credential_version' => 1, 'remember_token' => null];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'credential_version' => 'integer',
        ];
    }

    /** @return HasMany<Guardian, $this> */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    /** @return BelongsToMany<Family, $this> */
    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'guardians');
    }

    /** @return HasMany<RoleAssignment, $this> */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }
}
