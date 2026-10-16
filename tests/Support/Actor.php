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
    /** An admin of another church ("other"), who is also a parent there. */
    case OtherChurch = 'other-church admin';

    /** @return list<self> anyone with an active role in the world's church */
    public static function staff(): array
    {
        return [self::ChurchAdmin, self::HomeAdmin, self::ChurchLifeshaper, self::HomeLifeshaper, self::BothHats, self::HomeParentLifeshaper, self::AwayAdmin, self::AwayLifeshaper];
    }

    /** @return list<self> staff who can see the world's family (AccessScope::canSeeFamily) */
    public static function seeingFamily(): array
    {
        return [self::ChurchAdmin, self::HomeAdmin, self::ChurchLifeshaper, self::HomeLifeshaper, self::BothHats, self::HomeParentLifeshaper];
    }

    /** @return list<self> staff who may edit it (AccessScope::canEditFamily) */
    public static function editingFamily(): array
    {
        return [self::ChurchAdmin, self::HomeAdmin];
    }

    /** @return list<self> parents in the world's church, each with a family of their own */
    public static function parents(): array
    {
        return [self::Parent, self::OtherParent, self::BothHats, self::HomeParentLifeshaper, self::RevokedParent];
    }
}
