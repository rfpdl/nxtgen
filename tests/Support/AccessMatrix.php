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
        return [
            'church.home' => [AccessRowEnum::StaffArea, Actor::staff()],
            'church.poster' => [AccessRowEnum::StaffArea, Actor::staff()],
            'church.families.index' => [AccessRowEnum::FamilyList, Actor::staff()],
            'church.families.export' => [AccessRowEnum::FamilyList, Actor::staff()],
            'church.children.index' => [AccessRowEnum::FamilyList, Actor::staff()],
            'church.families.register' => [AccessRowEnum::DoorSearch, Actor::staff()],
            'church.families.store' => [AccessRowEnum::RegisterFamily, Actor::staff()],
            'church.families.children.store' => [AccessRowEnum::AddChild, Actor::staff()],
            'church.families.show' => [AccessRowEnum::ViewFamily, Actor::seeingFamily()],
            'church.families.update' => [AccessRowEnum::EditFamily, Actor::editingFamily()],
            'church.children.update' => [AccessRowEnum::EditFamily, Actor::editingFamily()],
            // Anyone with a staff role in the church (DECISIONS 2026-10-17, later still — Roy): a Lifeshaper at the door
            // helps a visiting family, so the other satellite's staff may too; revoked roles and parents may not.
            'church.families.resend' => [AccessRowEnum::ResendLink, Actor::staff()],
            'church.families.email' => [AccessRowEnum::CorrectContact, Actor::editingFamily()],
            'church.children.status' => [AccessRowEnum::SetChildStatus, Actor::editingFamily()],
            'church.children.level' => [AccessRowEnum::SetChildLevel, [Actor::ChurchAdmin]],
            'church.children.photo-consent' => [AccessRowEnum::ChildPhoto, Actor::seeingFamily()],
            'church.children.photo' => [AccessRowEnum::ChildPhoto, Actor::seeingFamily()],
            // Name cards (DECISIONS 2026-10-29): staff who may open the family file a new card; parents tell a Lifeshaper.
            'church.card-requests.store' => [AccessRowEnum::CardRequest, Actor::seeingFamily()],
            // Cards (name cards 03): the page for every admin (their own satellites' requests); moving Home's request needs
            // an admin over Home; giving a printed card, anyone who may open the family.
            'church.card-requests.index' => [AccessRowEnum::Cards, [Actor::ChurchAdmin, Actor::HomeAdmin, Actor::AwayAdmin]],
            // Print lists (name cards 04): any admin makes one of the cards they see; Home's card on a list is downloaded and
            // marked printed only by an admin over Home (a list with none of theirs is not found).
            'church.card-requests.print-lists.store' => [AccessRowEnum::Cards, [Actor::ChurchAdmin, Actor::HomeAdmin, Actor::AwayAdmin]],
            'church.card-requests.print-lists.show' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'church.card-requests.print-lists.mark-printed' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'church.card-requests.mark-paid' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'church.card-requests.approve' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'church.card-requests.refuse' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'church.card-requests.mark-printed' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'church.card-requests.give' => [AccessRowEnum::CardRequest, Actor::seeingFamily()],
            // Answered from either family's page (there is no Duplicates page — DECISIONS 2026-10-17, after first look);
            // answering needs the new family.
            'church.duplicates.resolve' => [AccessRowEnum::ReviewDuplicates, Actor::editingFamily()],
            // People opens for every admin; adding or removing a home Lifeshaper needs the home satellite.
            'church.people.index' => [AccessRowEnum::GrantRoles, [Actor::ChurchAdmin, Actor::HomeAdmin, Actor::AwayAdmin]],
            'church.people.store' => [AccessRowEnum::GrantRoles, Actor::editingFamily()],
            'church.people.roles.destroy' => [AccessRowEnum::GrantRoles, Actor::editingFamily()],
            // Moving-Up (DECISIONS 2026-11-06): the whole church at once, so a church admin only — no satellite admin.
            'church.moving-up.index' => [AccessRowEnum::MovingUp, [Actor::ChurchAdmin]],
            'church.moving-up.store' => [AccessRowEnum::MovingUp, [Actor::ChurchAdmin]],
            'church.moving-up.children.put-back' => [AccessRowEnum::MovingUp, [Actor::ChurchAdmin]],
            'church.moving-up.put-back' => [AccessRowEnum::MovingUp, [Actor::ChurchAdmin]],
            // Classes (DECISIONS 2026-10-26): the page opens for every admin; Home's class is changed only by an admin
            // over Home.
            'church.schedules.index' => [AccessRowEnum::ManageClasses, [Actor::ChurchAdmin, Actor::HomeAdmin, Actor::AwayAdmin]],
            'church.schedules.store' => [AccessRowEnum::ManageClasses, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'church.schedules.update' => [AccessRowEnum::ManageClasses, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'church.schedules.retire' => [AccessRowEnum::ManageClasses, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            // The door (DECISIONS 2026-10-26): every staff member opens it (their own satellites' classes); checking in at
            // Home's class, and undoing it, needs a Role covering Home.
            'church.door.show' => [AccessRowEnum::CheckIn, Actor::staff()],
            'church.door.check-in' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'church.door.undo' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            // Who's in (DECISIONS 2026-10-28): the door's people; marking a Home child present, as Undo.
            'church.door.whos-in' => [AccessRowEnum::CheckIn, Actor::staff()],
            'church.door.present' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'church.door.head-count' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            // Attendance (DECISIONS 2026-10-26: reports are admins only): Home's class, only for an admin over Home.
            'church.attendance.show' => [AccessRowEnum::Attendance, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'church.attendance.export' => [AccessRowEnum::Attendance, [Actor::ChurchAdmin, Actor::HomeAdmin]],
            'family.show' => [AccessRowEnum::OwnFamily, Actor::parents()],
            'family.update' => [AccessRowEnum::OwnFamily, Actor::parents()],
            'family.children.store' => [AccessRowEnum::OwnFamily, Actor::parents()],
            'family.children.update' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
            'family.children.photo' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
            'family.children.photo-consent.give' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
            'family.children.photo-consent' => [AccessRowEnum::OwnFamily, [Actor::Parent]],
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
