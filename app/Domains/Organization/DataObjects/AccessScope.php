<?php

declare(strict_types=1);

namespace App\Domains\Organization\DataObjects;

use App\Data\Models\Family;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

/**
 * What one staff member may do in one church (plan §3.2) — the ONE owner of these answers. Features ask it;
 * nothing else compares roles. Built from active role assignments in this church only.
 *
 *   church admin     → everything in the church
 *   satellite admin  → see + edit families whose home satellite is theirs; manage that satellite's classes
 *   class admin      → that class (Release 2)
 *   Lifeshaper       → see their satellites' families, register families, add children, resend links
 */
final readonly class AccessScope
{
    /**
     * @param  list<int>  $adminBranchIds
     * @param  list<int>  $lifeshaperBranchIds
     * @param  list<int>  $adminSeriesIds
     */
    public function __construct(
        public int $organizationId,
        public bool $isChurchAdmin,
        public bool $isChurchLifeshaper,
        public array $adminBranchIds,
        public array $lifeshaperBranchIds,
        public array $adminSeriesIds,
    ) {}

    public function canWorkHere(): bool
    {
        return $this->isChurchAdmin || $this->isChurchLifeshaper
            || $this->adminBranchIds !== [] || $this->lifeshaperBranchIds !== [] || $this->adminSeriesIds !== [];
    }

    /**
     * Satellites whose families this person can list. Null = every satellite (church-wide role).
     *
     * @return list<int>|null
     */
    public function visibleBranchIds(): ?array
    {
        if ($this->isChurchAdmin || $this->isChurchLifeshaper) {
            return null;
        }

        return array_values(array_unique([...$this->adminBranchIds, ...$this->lifeshaperBranchIds]));
    }

    public function canSeeFamily(Family $family): bool
    {
        if ($family->organization_id !== $this->organizationId) {
            return false;
        }

        $visible = $this->visibleBranchIds();

        return $visible === null || in_array($family->home_branch_id, $visible, true);
    }

    public function canEditFamily(Family $family): bool
    {
        return $family->organization_id === $this->organizationId
            && ($this->isChurchAdmin || in_array($family->home_branch_id, $this->adminBranchIds, true));
    }

    /** Registering and adding a child are church-wide acts for any staff (same umbrella, plan §3.2). */
    public function canRegisterFamilies(): bool
    {
        return $this->canWorkHere();
    }

    /**
     * Resend a confirmation/claim link (plan §3.2): church admin, any Lifeshaper (they meet the parent at the door),
     * or the admin of the family's home satellite. Not a class admin.
     */
    public function canResendLink(Family $family): bool
    {
        return $family->organization_id === $this->organizationId
            && ($this->isChurchAdmin || $this->isChurchLifeshaper || $this->lifeshaperBranchIds !== []
                || in_array($family->home_branch_id, $this->adminBranchIds, true));
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
        return $this->isChurchAdmin || $this->adminBranchIds !== [];
    }

    /**
     * The role-grant ceiling (plan §3.2): a church admin grants anything in this church; a satellite admin only
     * Lifeshaper, only in their own satellite. Removing a role uses the same rule. The caller still checks a
     * branch id belongs to this church (GetBranchJob) — this object knows ids, not ownership. Class roles
     * (event series) arrive with Release 2.
     */
    public function canGrant(RoleEnum $role, ScopeTypeEnum $type, int $scopeId): bool
    {
        if ($type === ScopeTypeEnum::EventSeries) {
            return false;
        }

        if ($this->isChurchAdmin) {
            return $type !== ScopeTypeEnum::Organization || $scopeId === $this->organizationId;
        }

        return $role === RoleEnum::Lifeshaper && $type === ScopeTypeEnum::Branch
            && in_array($scopeId, $this->adminBranchIds, true);
    }
}
