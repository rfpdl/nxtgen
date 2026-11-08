<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects\Concerns;

use App\Data\Models\Satellite;
use App\Data\Models\Schedule;

/**
 * AccessScope's answers about classes (GLOSSARY "Schedule", shown as "class"): the Classes page, the door and
 * Attendance — for church and satellite admins as before, and for a class admin (TODO #9), whose Role is at ONE class:
 * its door, its Who's in, its Attendance, editing and retiring it. Plan §3.2's "Class admin (S)" column.
 */
trait ClassRules
{
    public function isClassAdmin(): bool
    {
        return $this->adminSchedules !== [];
    }

    /** The Classes page: church admins, satellite admins (DECISIONS 2026-10-26) and class admins (their class only). */
    public function canManageClasses(): bool
    {
        return $this->isAnyAdmin() || $this->isClassAdmin();
    }

    /**
     * The satellites whose classes this person manages — adds, edits, retires: an admin's own (this one), or none. A
     * class admin's class is canManageSchedule's.
     *
     * @return list<int>
     */
    public function classSatelliteIds(): array
    {
        return $this->adminSatelliteIds;
    }

    /** Add a class at a satellite: an admin at their own. */
    public function canManageClassesAt(Satellite $satellite): bool
    {
        return $satellite->church_id === $this->churchId && in_array($satellite->id, $this->classSatelliteIds(), true);
    }

    /** Edit or retire this class: whoever manages its satellite's classes, or its own class admin. */
    public function canManageSchedule(Schedule $schedule): bool
    {
        return $schedule->church_id === $this->churchId
            && ($this->canManageClassesAt($schedule->satellite) || isset($this->adminSchedules[$schedule->id]));
    }

    /** @return list<int> the classes this person is class admin of */
    public function classAdminScheduleIds(): array
    {
        return array_keys($this->adminSchedules);
    }

    /**
     * The satellites whose classes this person checks children into at the door (DECISIONS 2026-10-26): those an Admin
     * or Lifeshaper Role covers (this one, or none). A class admin's own class comes on top (classAdminScheduleIds),
     * not its satellite's other classes. Which CHILDREN is not limited: any child of the church.
     *
     * @return list<int>
     */
    public function doorSatelliteIds(): array
    {
        return array_values(array_unique([...$this->adminSatelliteIds, ...$this->lifeshaperSatelliteIds]));
    }

    /** Check children in (and undo, mark present, confirm a head count) at this class. */
    public function canCheckInTo(Schedule $schedule): bool
    {
        return $schedule->church_id === $this->churchId && $this->canWorkHere()
            && (in_array($schedule->satellite_id, $this->doorSatelliteIds(), true) || isset($this->adminSchedules[$schedule->id]));
    }

    /** Attendance (reports are admins only — DECISIONS 2026-10-26): church, satellite and class admins. */
    public function canSeeAttendance(): bool
    {
        return $this->isAnyAdmin() || $this->isClassAdmin();
    }

    /**
     * The satellites whose Attendance this person may pick: an admin's own and a class admin's class's satellite (this
     * one), or none. Which CLASS there is canSeeAttendanceOf.
     *
     * @return list<int>
     */
    public function attendanceSatelliteIds(): array
    {
        return array_values(array_unique([...$this->adminSatelliteIds, ...array_values($this->adminSchedules)]));
    }

    /** This class's Attendance: an admin of its satellite, or its own class admin. */
    public function canSeeAttendanceOf(Schedule $schedule): bool
    {
        return $schedule->church_id === $this->churchId
            && (in_array($schedule->satellite_id, $this->adminSatelliteIds, true) || isset($this->adminSchedules[$schedule->id]));
    }
}
