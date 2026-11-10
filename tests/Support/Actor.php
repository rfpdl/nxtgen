<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Everyone who might ask for a staff or parent page (plan §3.2's actor kinds, TODO #18), as AccessWorld makes them.
 * "Home" is the satellite the world's family lives at, and the one in every staff address the matrix opens
 * (`/home-sat/…`); "away" is the other one — its staff have no Role at Home, so Home's pages are 404 for them (spec
 * item 10).
 */
enum Actor: string
{
    case Anonymous = 'anonymous';
    case Parent = 'parent';
    case OtherParent = 'other parent';
    /** Admin at both satellites (there is no church-wide Role — spec item 9): an admin at Home, as at Away. */
    case HomeAndAwayAdmin = 'admin at Home and Away';
    case HomeAdmin = 'satellite admin';
    /** A Lifeshaper at both satellites. */
    case HomeAndAwayLifeshaper = 'Lifeshaper at Home and Away';
    case HomeLifeshaper = 'satellite Lifeshaper';
    /** A parent (their family lives "away") who is also a home Lifeshaper. */
    case BothHats = 'parent + Lifeshaper';
    /** A parent whose own family lives at Home, where they are also a Lifeshaper — their scope covers their family. */
    case HomeParentLifeshaper = 'parent + Lifeshaper, both at home';
    /** An admin at both satellites whose Roles were switched off. */
    case Revoked = 'revoked staff';
    /** A Home satellite admin whose role was switched off. */
    case RevokedSatelliteAdmin = 'revoked satellite admin';
    /** A parent (family at Home) whose Lifeshaper role was switched off: still a parent, no longer staff. */
    case RevokedParent = 'revoked staff, still a parent';
    case AwayAdmin = 'other-satellite admin';
    case AwayLifeshaper = 'other-satellite Lifeshaper';
    /** The Admin Role at Home's 1PM — the world's class — only (TODO #9): a class admin. */
    case ClassAdmin = 'class admin of Home 1PM';
    /** An admin of another church ("other", at its satellite `/other`). */
    case OtherChurch = 'other-church admin';

    /** @return list<self> anyone with an active Role at Home — who may open Home's staff pages (`/home-sat/…`) */
    public static function staff(): array
    {
        return [self::HomeAndAwayAdmin, self::HomeAdmin, self::HomeAndAwayLifeshaper, self::HomeLifeshaper, self::BothHats, self::HomeParentLifeshaper, self::ClassAdmin];
    }

    /** @return list<self> staff who can see the world's family (AccessScope::canSeeFamily) — a class admin at Home too */
    public static function seeingFamily(): array
    {
        return [self::HomeAndAwayAdmin, self::HomeAdmin, self::HomeAndAwayLifeshaper, self::HomeLifeshaper, self::BothHats, self::HomeParentLifeshaper, self::ClassAdmin];
    }

    /**
     * @return list<self> staff whose Role covers Home, where the world's family lives and its class meets — who may
     *                    check children in at Home's 1PM (AccessScope::canCheckInTo) — its class admin too
     */
    public static function coveringHome(): array
    {
        return [self::HomeAndAwayAdmin, self::HomeAdmin, self::HomeAndAwayLifeshaper, self::HomeLifeshaper, self::BothHats, self::HomeParentLifeshaper, self::ClassAdmin];
    }

    /** @return list<self> staff who may edit it (AccessScope::canEditFamily) */
    public static function editingFamily(): array
    {
        return [self::HomeAndAwayAdmin, self::HomeAdmin];
    }

    /**
     * @return list<self> anyone with an active admin Role at Home (a church admin's counts there) — who may open Home's
     *                    Admin (a class admin opens only Classes and Attendance there)
     */
    public static function admins(): array
    {
        return [self::HomeAndAwayAdmin, self::HomeAdmin];
    }

    /**
     * Those of `$actors` who may open the Admin area (Roy 2026-11-06): anyone else serving at Home is sent to Serve
     * (AccessMatrix::expected), on top of its own row.
     *
     * @param  list<self>  $actors
     * @return list<self>
     */
    public static function inAdmin(array $actors): array
    {
        return array_values(array_filter($actors, fn (self $a): bool => in_array($a, self::admins(), true)));
    }

    /** @return list<self> parents in the world's church, each with a family of their own */
    public static function parents(): array
    {
        return [self::Parent, self::OtherParent, self::BothHats, self::HomeParentLifeshaper, self::RevokedParent];
    }
}
