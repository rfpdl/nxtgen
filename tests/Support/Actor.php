<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Everyone who might ask for a staff or parent page (plan §3.2's actor kinds, TODO #18), as AccessWorld makes them.
 * "Home" is the satellite the world's family lives at; "away" is the other one.
 */
enum Actor: string
{
    case Anonymous = 'anonymous';
    case Parent = 'parent';
    case OtherParent = 'other parent';
    case ChurchAdmin = 'church admin';
    case HomeAdmin = 'satellite admin';
    case ChurchLifeshaper = 'church Lifeshaper';
    case HomeLifeshaper = 'satellite Lifeshaper';
    /** A parent (their family lives "away") who is also a home Lifeshaper. */
    case BothHats = 'parent + Lifeshaper';
    /** A parent whose own family lives at Home, where they are also a Lifeshaper — their scope covers their family. */
    case HomeParentLifeshaper = 'parent + Lifeshaper, both at home';
    /** A church admin whose role was switched off. */
    case Revoked = 'revoked staff';
    /** A Home satellite admin whose role was switched off. */
    case RevokedSatelliteAdmin = 'revoked satellite admin';
    /** A parent (family at Home) whose Lifeshaper role was switched off: still a parent, no longer staff. */
    case RevokedParent = 'revoked staff, still a parent';
    case AwayAdmin = 'other-satellite admin';
    case AwayLifeshaper = 'other-satellite Lifeshaper';
    /** The Admin Role at Home's 1PM — the world's class — only (TODO #9): a class admin. */
    case ClassAdmin = 'class admin of Home 1PM';
    /** An admin of another church ("other"), who is also a parent there. */
    case OtherChurch = 'other-church admin';

    /** @return list<self> anyone with an active role in the world's church */
    public static function staff(): array
    {
        return [self::ChurchAdmin, self::HomeAdmin, self::ChurchLifeshaper, self::HomeLifeshaper, self::BothHats, self::HomeParentLifeshaper, self::AwayAdmin, self::AwayLifeshaper, self::ClassAdmin];
    }

    /** @return list<self> staff who can see the world's family (AccessScope::canSeeFamily) — a class admin at Home too */
    public static function seeingFamily(): array
    {
        return [self::ChurchAdmin, self::HomeAdmin, self::ChurchLifeshaper, self::HomeLifeshaper, self::BothHats, self::HomeParentLifeshaper, self::ClassAdmin];
    }

    /**
     * @return list<self> staff whose Role covers Home, where the world's family lives and its class meets — who may
     *                    check children in at Home's 1PM (AccessScope::canCheckInTo) — its class admin too
     */
    public static function coveringHome(): array
    {
        return [self::ChurchAdmin, self::HomeAdmin, self::ChurchLifeshaper, self::HomeLifeshaper, self::BothHats, self::HomeParentLifeshaper, self::ClassAdmin];
    }

    /** @return list<self> staff who may edit it (AccessScope::canEditFamily) */
    public static function editingFamily(): array
    {
        return [self::ChurchAdmin, self::HomeAdmin];
    }

    /**
     * @return list<self> anyone with an active church or satellite admin Role in the world's church — who may open the
     *                    Admin areas (a class admin opens only Classes and Attendance there)
     */
    public static function admins(): array
    {
        return [self::ChurchAdmin, self::HomeAdmin, self::AwayAdmin];
    }

    /**
     * Those of `$actors` who may open the Admin areas (Roy 2026-11-06): an Admin address is 404 for everyone else, on
     * top of its own row.
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
