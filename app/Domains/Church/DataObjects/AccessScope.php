<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Domains\Church\DataObjects\Concerns\ClassRules;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

/**
 * What one staff member may do at ONE satellite — the one in the address (plan §3.2; spec item 10) — the ONE owner of
 * these answers. Features ask it; nothing else compares roles. Built from active role assignments there only
 * (GetAccessScopeJob), so every satellite list below is that satellite or nothing (DECISIONS 2026-11-08). A
 * church-wide Role counts as the same Role at this satellite; a church admin keeps the church-wide acts (Level,
 * Moving-Up, granting) until issue 06.
 *
 *   church admin     → everything in the church
 *   satellite admin  → see + edit families whose home satellite is theirs; manage that satellite's Schedules (classes);
 *                      see its Attendance
 *   class admin      → that class (a Schedule — TODO #9): its door, Who's in, Attendance, edit/retire it; families of
 *                      its satellite as a Lifeshaper there (ClassRules)
 *   Lifeshaper       → see their satellites' families, register families, add children
 *   any staff role   → re-send any family's confirmation link (a visiting family too); check children in at the
 *                      door of the satellite(s) the Role covers — any child of the church (DECISIONS 2026-10-26)
 */
final readonly class AccessScope
{
    use ClassRules;

    /**
     * @param  list<int>  $adminSatelliteIds
     * @param  list<int>  $lifeshaperSatelliteIds
     * @param  array<int, int>  $adminSchedules  class admin (TODO #9): schedule id => its satellite id
     */
    public function __construct(
        public int $churchId,
        public bool $isChurchAdmin,
        public array $adminSatelliteIds,
        public array $lifeshaperSatelliteIds,
        public array $adminSchedules,
    ) {}

    public function canWorkHere(): bool
    {
        return $this->adminSatelliteIds !== [] || $this->lifeshaperSatelliteIds !== [] || $this->isClassAdmin();
    }

    /**
     * Satellites whose families this person can list: this one, or none.
     *
     * @return list<int>
     */
    public function visibleSatelliteIds(): array
    {
        // A class admin works at their class's satellite, as a Lifeshaper there (TODO #9).
        return array_values(array_unique([...$this->adminSatelliteIds, ...$this->lifeshaperSatelliteIds, ...array_values($this->adminSchedules)]));
    }

    public function canSeeFamily(Family $family): bool
    {
        if ($family->church_id !== $this->churchId) {
            return false;
        }

        return in_array($family->home_satellite_id, $this->visibleSatelliteIds(), true);
    }

    public function canEditFamily(Family $family): bool
    {
        return $family->church_id === $this->churchId
            && in_array($family->home_satellite_id, $this->adminSatelliteIds, true);
    }

    /**
     * Edit a family's details, its guardians and its children's details (Roy 2026-11-06): staff who can open the
     * family — a Lifeshaper of its satellite as well as its admins. Everything else an editor does (the email, a
     * child's status, Cards' moves, duplicates, moving the family to another satellite) stays canEditFamily's.
     */
    public function canEditFamilyDetails(Family $family): bool
    {
        return $this->canSeeFamily($family);
    }

    /** Registering and adding a child are church-wide acts for any staff (same umbrella, plan §3.2). */
    public function canRegisterFamilies(): bool
    {
        return $this->canWorkHere();
    }

    /**
     * Re-send a confirmation/claim link: anyone with an active staff role in this church, whichever satellite the
     * family lives at (DECISIONS 2026-10-17, later still — Roy): a Lifeshaper at the door helps a visiting family,
     * and sending an email changes no data.
     */
    public function canResendLink(Family $family): bool
    {
        return $family->church_id === $this->churchId && $this->canWorkHere();
    }

    /** Correct a child's Level outside Moving-Up (plan §3.2): church admin only. */
    public function canSetLevel(): bool
    {
        return $this->isChurchAdmin;
    }

    /** Moving-Up, and putting the last one back (DECISIONS 2026-11-06): church admin only — the whole church at once. */
    public function canMoveUp(): bool
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
     * Answering possible duplicates (on a family's page): church admins and satellite admins. WHICH pairs each may
     * answer is canEditFamily on the new family (plan §6).
     */
    public function canReviewDuplicates(): bool
    {
        return $this->isAnyAdmin();
    }

    /**
     * Links (GLOSSARY; Roy 2026-11-06): a satellite's own addresses on its poster — managed by church admins and
     * satellite admins. WHICH satellites' is canManageLinksAt, as for classes.
     */
    public function canManageLinks(): bool
    {
        return $this->isAnyAdmin();
    }

    /**
     * The satellites whose Links this person manages: an admin's own (this one), or none.
     *
     * @return list<int>
     */
    public function linkSatelliteIds(): array
    {
        return $this->classSatelliteIds();
    }

    public function canManageLinksAt(Satellite $satellite): bool
    {
        return $satellite->church_id === $this->churchId && in_array($satellite->id, $this->linkSatelliteIds(), true);
    }

    /**
     * The Admin areas (Roy 2026-11-06): any admin Role here — church, satellite, or one class (TODO #9). A class
     * admin opens only Classes and Attendance there (canUseFullAdmin is everything else). Everyone else serves in Serve.
     */
    public function canUseAdmin(): bool
    {
        return $this->isAnyAdmin() || $this->isClassAdmin();
    }

    /** Every Admin page — families, Excel, Children, Cards, Links, People…: a church or satellite admin, not a class admin. */
    public function canUseFullAdmin(): bool
    {
        return $this->isAnyAdmin();
    }

    /**
     * The Cards page (name cards, DECISIONS 2026-10-29): church admins and satellite admins. WHICH requests each may
     * see is cardSatelliteIds; which each may move (pay, approve, refuse, print) is canEditFamily on the child's family.
     */
    public function canManageCards(): bool
    {
        return $this->isAnyAdmin();
    }

    /**
     * The satellites whose families' Card requests this person sees on Cards: an admin's own (this one), or none.
     *
     * @return list<int>
     */
    public function cardSatelliteIds(): array
    {
        return $this->adminSatelliteIds;
    }

    private function isAnyAdmin(): bool
    {
        return $this->adminSatelliteIds !== [];
    }

    /**
     * The role-grant ceiling (plan §3.2): a church admin grants anything in this church; a satellite admin only
     * Lifeshaper, only in their own satellite. Removing a role uses the same rule. The caller still checks a
     * satellite id belongs to this church (GetSatelliteJob) — this object knows ids, not ownership. Schedule roles
     * (shown as "class") arrive with Release 2.
     */
    public function canGrant(RoleEnum $role, RoleScope $scope): bool
    {
        // A class admin (TODO #9): the Admin Role at a class of this church (its satellite known) — given by a church
        // admin anywhere, by a satellite admin at their satellite (plan §3.2).
        if ($scope->type === ScopeTypeEnum::Schedule) {
            return $role === RoleEnum::Admin && $scope->satelliteId !== null
                && ($this->isChurchAdmin || in_array($scope->satelliteId, $this->adminSatelliteIds, true));
        }

        if ($this->isChurchAdmin) {
            return $scope->type !== ScopeTypeEnum::Church || $scope->id === $this->churchId;
        }

        return $role === RoleEnum::Lifeshaper && $scope->type === ScopeTypeEnum::Satellite
            && in_array($scope->id, $this->adminSatelliteIds, true);
    }
}
