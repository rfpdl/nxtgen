<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Family;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

/**
 * What one staff member may do in one church (plan §3.2) — the ONE owner of these answers. Features ask it;
 * nothing else compares roles. Built from active role assignments in this church only.
 *
 *   church admin     → everything in the church
 *   satellite admin  → see + edit families whose home satellite is theirs; manage that satellite's Schedules
 *   schedule admin   → that Schedule (shown as "class"; Release 2)
 *   Lifeshaper       → see their satellites' families, register families, add children, resend links
 */
final readonly class AccessScope
{
    /**
     * @param  list<int>  $adminSatelliteIds
     * @param  list<int>  $lifeshaperSatelliteIds
     * @param  list<int>  $adminScheduleIds
     */
    public function __construct(
        public int $churchId,
        public bool $isChurchAdmin,
        public bool $isChurchLifeshaper,
        public array $adminSatelliteIds,
        public array $lifeshaperSatelliteIds,
        public array $adminScheduleIds,
    ) {}

    public function canWorkHere(): bool
    {
        return $this->isChurchAdmin || $this->isChurchLifeshaper
            || $this->adminSatelliteIds !== [] || $this->lifeshaperSatelliteIds !== [] || $this->adminScheduleIds !== [];
    }

    /**
     * Satellites whose families this person can list. Null = every satellite (church-wide role).
     *
     * @return list<int>|null
     */
    public function visibleSatelliteIds(): ?array
    {
        if ($this->isChurchAdmin || $this->isChurchLifeshaper) {
            return null;
        }

        return array_values(array_unique([...$this->adminSatelliteIds, ...$this->lifeshaperSatelliteIds]));
    }

    public function canSeeFamily(Family $family): bool
    {
        if ($family->church_id !== $this->churchId) {
            return false;
        }

        $visible = $this->visibleSatelliteIds();

        return $visible === null || in_array($family->home_satellite_id, $visible, true);
    }

    public function canEditFamily(Family $family): bool
    {
        return $family->church_id === $this->churchId
            && ($this->isChurchAdmin || in_array($family->home_satellite_id, $this->adminSatelliteIds, true));
    }

    /** Registering and adding a child are church-wide acts for any staff (same umbrella, plan §3.2). */
    public function canRegisterFamilies(): bool
    {
        return $this->canWorkHere();
    }

    /**
     * Resend a confirmation/claim link (plan §3.2): church admin, any Lifeshaper (they meet the parent at the door),
     * or the admin of the family's home satellite. Not a schedule admin.
     */
    public function canResendLink(Family $family): bool
    {
        return $family->church_id === $this->churchId
            && ($this->isChurchAdmin || $this->isChurchLifeshaper || $this->lifeshaperSatelliteIds !== []
                || in_array($family->home_satellite_id, $this->adminSatelliteIds, true));
    }

    /** Correct a child's Level outside Moving-Up (plan §3.2): church admin only. */
    public function canSetLevel(): bool
    {
        return $this->isChurchAdmin;
    }

    public function canMergeRecords(): bool
    {
        return $this->isChurchAdmin;
    }

    public function canManageChurch(): bool
    {
        return $this->isChurchAdmin;
    }

    /** The People page: church admins and satellite admins. */
    public function canManagePeople(): bool
    {
        return $this->isAnyAdmin();
    }

    /**
     * The possible-duplicates screen: church admins and satellite admins. WHICH pairs each may review is
     * canEditFamily on the new family (plan §6).
     */
    public function canReviewDuplicates(): bool
    {
        return $this->isAnyAdmin();
    }

    private function isAnyAdmin(): bool
    {
        return $this->isChurchAdmin || $this->adminSatelliteIds !== [];
    }

    /**
     * The role-grant ceiling (plan §3.2): a church admin grants anything in this church; a satellite admin only
     * Lifeshaper, only in their own satellite. Removing a role uses the same rule. The caller still checks a
     * satellite id belongs to this church (GetSatelliteJob) — this object knows ids, not ownership. Schedule roles
     * (shown as "class") arrive with Release 2.
     */
    public function canGrant(RoleEnum $role, RoleScope $scope): bool
    {
        if ($scope->type === ScopeTypeEnum::Schedule) {
            return false;
        }

        if ($this->isChurchAdmin) {
            return $scope->type !== ScopeTypeEnum::Church || $scope->id === $this->churchId;
        }

        return $role === RoleEnum::Lifeshaper && $scope->type === ScopeTypeEnum::Satellite
            && in_array($scope->id, $this->adminSatelliteIds, true);
    }
}
