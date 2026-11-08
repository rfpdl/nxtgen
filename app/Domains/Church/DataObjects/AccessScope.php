<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Domains\Church\DataObjects\Concerns\ClassRules;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

/**
 * What one staff member may do at ONE satellite — the one in the address (plan §3.2; spec items 9–10) — the ONE owner
 * of these answers. Features ask it; nothing else compares roles. Built from active role assignments there only
 * (GetAccessScopeJob), so every satellite list below is that satellite or nothing (DECISIONS 2026-11-08). There is no
 * church-wide Role (spec item 9):
 *
 *   satellite admin  → everything at their satellite: its families, children, classes, Attendance, Cards, Links,
 *                      People (any Role there), Moving-Up and Level fixes (DECISIONS 2026-11-08)
 *   class admin      → that class (a Schedule — TODO #9): its door, Who's in, Attendance, edit/retire it; families of
 *                      its satellite as a Lifeshaper there (ClassRules)
 *   Lifeshaper       → see their satellite's families, register families, add children
 *   any staff role   → re-send any family's confirmation link (a visiting family too); check children in at the
 *                      door of their satellite — any child of the church (DECISIONS 2026-10-26)
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

    /** Correct a child's Level outside Moving-Up (plan §3.2): an admin here (DECISIONS 2026-11-08). */
    public function canSetLevel(): bool
    {
        return $this->isAnyAdmin();
    }

    /** Moving-Up, and putting the last one back: an admin here, for this satellite's children (DECISIONS 2026-11-08). */
    public function canMoveUp(): bool
    {
        return $this->isAnyAdmin();
    }

    /** The People page: the satellite's admins. */
    public function canManagePeople(): bool
    {
        return $this->isAnyAdmin();
    }

    /**
     * Answering possible duplicates (on a family's page): the satellite's admins. WHICH pairs each may
     * answer is canEditFamily on the new family (plan §6).
     */
    public function canReviewDuplicates(): bool
    {
        return $this->isAnyAdmin();
    }

    /**
     * Links (GLOSSARY; Roy 2026-11-06): a satellite's own addresses on its poster — managed by its admins. WHICH
     * satellites' is canManageLinksAt, as for classes.
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
     * The Admin areas (Roy 2026-11-06): any admin Role here — the satellite, or one class (TODO #9). A class
     * admin opens only Classes and Attendance there (canUseFullAdmin is everything else). Everyone else serves in Serve.
     */
    public function canUseAdmin(): bool
    {
        return $this->isAnyAdmin() || $this->isClassAdmin();
    }

    /** Every Admin page — families, Excel, Children, Cards, Links, People…: a satellite admin, not a class admin. */
    public function canUseFullAdmin(): bool
    {
        return $this->isAnyAdmin();
    }

    /**
     * The Cards page (name cards, DECISIONS 2026-10-29): the satellite's admins. WHICH requests each may
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
     * The role-grant ceiling (spec item 9): an admin here gives and removes any Role at this satellite — Admin or
     * Lifeshaper — and the Admin Role at its classes (a class admin, TODO #9: the class's satellite known). Nobody gives
     * a Role anywhere else. Removing a role uses the same rule. The caller still checks a satellite id belongs to this
     * church (GetSatelliteJob) — this object knows ids, not ownership.
     */
    public function canGrant(RoleEnum $role, RoleScope $scope): bool
    {
        return match ($scope->type) {
            ScopeTypeEnum::Satellite => in_array($scope->id, $this->adminSatelliteIds, true),
            ScopeTypeEnum::Schedule => $role === RoleEnum::Admin && $scope->satelliteId !== null
                && in_array($scope->satelliteId, $this->adminSatelliteIds, true),
        };
    }
}
