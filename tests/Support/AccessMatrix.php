<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\AccessRowEnum;
use Illuminate\Routing\Route;
use ReflectionMethod;

/**
 * The access matrix (plan §3.2, TODO #18): every staff and parent route, the access row its Feature declares, and who
 * gets `ok`. Read by AccessMatrixTest (which proves every cell) and by the test map (`make test-map`, TODO #10), which
 * shows it — one list, so the page can never say something the tests do not prove.
 */
final class AccessMatrix
{
    /**
     * Route name => the access row its Feature declares, and who gets `ok`. Every other signed-in actor gets 404;
     * anonymous is sent to sign in.
     *
     * @return array<string, array{0: AccessRowEnum, 1: list<Actor>}>
     */
    public static function rows(): array
    {
        // The family pages both NxtGen areas serve (Roy 2026-11-06): the same rows, and in Admin only for admins.
        $familyPages = [
            'families.index' => [AccessRowEnum::FamilyList, Actor::staff()],
            'families.register' => [AccessRowEnum::DoorSearch, Actor::staff()],
            'families.store' => [AccessRowEnum::RegisterFamily, Actor::staff()],
            'families.children.store' => [AccessRowEnum::AddChild, Actor::staff()],
            'families.show' => [AccessRowEnum::ViewFamily, Actor::seeingFamily()],
            // Staff who can open the family edit its details (Roy 2026-11-06): a Lifeshaper of its satellite too.
            'families.update' => [AccessRowEnum::EditFamily, Actor::seeingFamily()],
            'children.update' => [AccessRowEnum::EditFamily, Actor::seeingFamily()],
            // Anyone with a staff role in the church (DECISIONS 2026-10-17, later still — Roy): a Lifeshaper at the door
            // helps a visiting family, so the other satellite's staff may too; revoked roles and parents may not.
            'families.resend' => [AccessRowEnum::ResendLink, Actor::staff()],
            'children.photo-consent' => [AccessRowEnum::ChildPhoto, Actor::seeingFamily()],
            'children.photo' => [AccessRowEnum::ChildPhoto, Actor::seeingFamily()],
            // Name cards (DECISIONS 2026-10-29): staff who may open the family file a new card, and give a printed one.
            'card-requests.store' => [AccessRowEnum::CardRequest, Actor::seeingFamily()],
            'card-requests.give' => [AccessRowEnum::CardRequest, Actor::seeingFamily()],
        ];
        $rows = ['church.home' => [AccessRowEnum::StaffArea, Actor::staff()]];
        foreach ($familyPages as $name => [$row, $actors]) {
            $rows["nxtgen.admin.{$name}"] = [$row, Actor::inAdmin($actors)];
            $rows["nxtgen.serve.{$name}"] = [$row, $actors];
        }

        return [
            ...$rows,
            // ── NxtGen Admin: admins only ──
            'nxtgen.admin.families.export' => [AccessRowEnum::FamilyList, Actor::admins()],
            'nxtgen.admin.children.index' => [AccessRowEnum::FamilyList, Actor::admins()],
            'nxtgen.admin.families.email' => [AccessRowEnum::CorrectContact, Actor::editingFamily()],
            'nxtgen.admin.children.status' => [AccessRowEnum::SetChildStatus, Actor::editingFamily()],
            'nxtgen.admin.children.level' => [AccessRowEnum::SetChildLevel, [Actor::ChurchAdmin]],
            // A photo deleted for good (TODO #25): it cannot be undone, so an admin who may edit the family — never a
            // Lifeshaper.
            'nxtgen.admin.children.photo.destroy' => [AccessRowEnum::DeleteChildPhoto, Actor::editingFamily()],
            // Cards (name cards 03): the page for every admin (their own satellites' requests); moving Home's request needs
            // an admin over Home.
            'nxtgen.admin.card-requests.index' => [AccessRowEnum::Cards, Actor::admins()],
            // Print lists (name cards 04): any admin makes one of the cards they see; Home's card on a list is downloaded and
            // marked printed only by an admin over Home (a list with none of theirs is not found).
            'nxtgen.admin.card-requests.print-lists.store' => [AccessRowEnum::Cards, Actor::admins()],
            'nxtgen.admin.card-requests.print-lists.show' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'nxtgen.admin.card-requests.print-lists.mark-printed' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'nxtgen.admin.card-requests.mark-paid' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'nxtgen.admin.card-requests.approve' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'nxtgen.admin.card-requests.refuse' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'nxtgen.admin.card-requests.mark-printed' => [AccessRowEnum::Cards, Actor::editingFamily()],
            // Answered from either family's page (there is no Duplicates page — DECISIONS 2026-10-17, after first look);
            // answering needs the new family.
            'nxtgen.admin.duplicates.resolve' => [AccessRowEnum::ReviewDuplicates, Actor::editingFamily()],
            // Moving-Up (DECISIONS 2026-11-06): the whole church at once, so a church admin only — no satellite admin.
            'nxtgen.admin.moving-up.index' => [AccessRowEnum::MovingUp, [Actor::ChurchAdmin]],
            'nxtgen.admin.moving-up.store' => [AccessRowEnum::MovingUp, [Actor::ChurchAdmin]],
            'nxtgen.admin.moving-up.children.put-back' => [AccessRowEnum::MovingUp, [Actor::ChurchAdmin]],
            'nxtgen.admin.moving-up.put-back' => [AccessRowEnum::MovingUp, [Actor::ChurchAdmin]],
            // Classes (DECISIONS 2026-10-26): the page opens for every admin; Home's class is changed only by an admin
            // over Home.
            'nxtgen.admin.schedules.index' => [AccessRowEnum::ManageClasses, Actor::admins()],
            'nxtgen.admin.schedules.store' => [AccessRowEnum::ManageClasses, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'nxtgen.admin.schedules.update' => [AccessRowEnum::ManageClasses, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'nxtgen.admin.schedules.retire' => [AccessRowEnum::ManageClasses, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            // Links (Roy 2026-11-06): the page opens for every admin; Home's Links are changed only by an admin over Home.
            'nxtgen.admin.links.index' => [AccessRowEnum::ManageLinks, Actor::admins()],
            'nxtgen.admin.links.store' => [AccessRowEnum::ManageLinks, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'nxtgen.admin.links.update' => [AccessRowEnum::ManageLinks, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'nxtgen.admin.links.move' => [AccessRowEnum::ManageLinks, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'nxtgen.admin.links.destroy' => [AccessRowEnum::ManageLinks, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            // Attendance (DECISIONS 2026-10-26: reports are admins only): Home's class, only for an admin over Home.
            'nxtgen.admin.attendance.show' => [AccessRowEnum::Attendance, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'nxtgen.admin.attendance.export' => [AccessRowEnum::Attendance, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            // ── Church-wide Admin ──
            // People opens for every admin; adding or removing a home Lifeshaper needs the home satellite.
            'admin.people.index' => [AccessRowEnum::GrantRoles, Actor::admins()],
            'admin.people.store' => [AccessRowEnum::GrantRoles, Actor::editingFamily()],
            'admin.people.roles.destroy' => [AccessRowEnum::GrantRoles, Actor::editingFamily()],
            // ── NxtGen Serve: any staff ──
            'nxtgen.serve.poster' => [AccessRowEnum::StaffArea, Actor::staff()],
            // The door (DECISIONS 2026-10-26): every staff member opens it (their own satellites' classes); checking in at
            // Home's class, and undoing it, needs a Role covering Home.
            'nxtgen.serve.door.show' => [AccessRowEnum::CheckIn, Actor::staff()],
            'nxtgen.serve.door.check-in' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'nxtgen.serve.door.undo' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            // Who's in (DECISIONS 2026-10-28): the door's people; marking a Home child present, as Undo.
            'nxtgen.serve.door.whos-in' => [AccessRowEnum::CheckIn, Actor::staff()],
            'nxtgen.serve.door.present' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'nxtgen.serve.door.head-count' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'family.show' => [AccessRowEnum::OwnFamily, Actor::parents()],
            'family.update' => [AccessRowEnum::OwnFamily, Actor::parents()],
            'family.children.store' => [AccessRowEnum::OwnFamily, Actor::parents()],
            'family.children.update' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
            'family.children.photo' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
            'family.children.photo-consent.give' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
            'family.children.photo-consent' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
            'family.children.photo.destroy' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
            'photos.child' => [AccessRowEnum::SeeChildPhoto, [Actor::Parent, ...Actor::seeingFamily()]],
        ];
    }

    /** What `$actor` gets on `$route`: `ok`, `404`, or `login` (signed out). */
    public static function expected(string $route, Actor $actor): string
    {
        [, $allowed] = self::rows()[$route];

        return match (true) {
            $actor === Actor::Anonymous => 'login',
            in_array($actor, $allowed, true) => 'ok',
            default => '404',
        };
    }

    /** The Feature a route's controller action serves, read from the action's source. */
    public static function featureOf(Route $route): string
    {
        $method = new ReflectionMethod((string) $route->getControllerClass(), $route->getActionMethod());
        $file = (string) $method->getFileName();
        $lines = array_slice((array) file($file), $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);
        preg_match('/serve\((\w+)::class/', implode('', array_map(strval(...), $lines)), $served);
        preg_match('/^use ([\w\\\\]+\\\\'.($served[1] ?? '-').');$/m', (string) file_get_contents($file), $import);

        return $import[1] ?? '';
    }
}
