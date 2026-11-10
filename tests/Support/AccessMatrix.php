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
     * Route name => the access row its Feature declares, and who gets `ok` at Home's address (`/home-sat/…`). Someone else
     * serving at Home who opens an Admin page is sent to Serve (spec item 10); every other signed-in actor gets 404;
     * anonymous is sent to sign in.
     *
     * @return array<string, array{0: AccessRowEnum, 1: list<Actor>}>
     */
    public static function rows(): array
    {
        // The family pages both areas serve (Roy 2026-11-06): the same rows, and in Admin only for admins.
        $familyPages = [
            'families.index' => [AccessRowEnum::FamilyList, Actor::staff()],
            'families.register' => [AccessRowEnum::DoorSearch, Actor::staff()],
            'families.store' => [AccessRowEnum::RegisterFamily, Actor::staff()],
            'families.children.store' => [AccessRowEnum::AddChild, Actor::staff()],
            'families.show' => [AccessRowEnum::ViewFamily, Actor::seeingFamily()],
            // Staff who can open the family edit its details (Roy 2026-11-06): a Lifeshaper of its satellite too.
            'families.update' => [AccessRowEnum::EditFamily, Actor::seeingFamily()],
            'children.update' => [AccessRowEnum::EditFamily, Actor::seeingFamily()],
            // Anyone with a staff role here (DECISIONS 2026-10-17, later still — Roy): a Lifeshaper at the door helps a
            // visiting family — from their own satellite's address; revoked roles and parents may not.
            'families.resend' => [AccessRowEnum::ResendLink, Actor::staff()],
            'children.photo-consent' => [AccessRowEnum::ChildPhoto, Actor::seeingFamily()],
            'children.photo' => [AccessRowEnum::ChildPhoto, Actor::seeingFamily()],
            // Name cards (DECISIONS 2026-10-29): staff who may open the family file a new card, and give a printed one.
            'card-requests.store' => [AccessRowEnum::CardRequest, Actor::seeingFamily()],
            'card-requests.give' => [AccessRowEnum::CardRequest, Actor::seeingFamily()],
        ];
        $rows = ['satellite.home' => [AccessRowEnum::StaffArea, Actor::staff()]];
        foreach ($familyPages as $name => [$row, $actors]) {
            $rows["admin.{$name}"] = [$row, Actor::inAdmin($actors)];
            $rows["serve.{$name}"] = [$row, $actors];
        }

        return [
            ...$rows,
            // ── Admin: admins only ──
            'admin.families.export' => [AccessRowEnum::FamilyList, Actor::admins()],
            'admin.children.index' => [AccessRowEnum::FamilyList, Actor::admins()],
            'admin.families.email' => [AccessRowEnum::CorrectContact, Actor::editingFamily()],
            'admin.children.status' => [AccessRowEnum::SetChildStatus, Actor::editingFamily()],
            'admin.children.level' => [AccessRowEnum::SetChildLevel, Actor::admins()],
            // A photo deleted for good (TODO #25): it cannot be undone, so an admin who may edit the family — never a
            // Lifeshaper.
            'admin.children.photo.destroy' => [AccessRowEnum::DeleteChildPhoto, Actor::editingFamily()],
            // Cards (name cards 03): the page for every admin (their own satellites' requests); moving Home's request needs
            // an admin over Home.
            'admin.card-requests.index' => [AccessRowEnum::Cards, Actor::admins()],
            // Print lists (name cards 04): any admin makes one of the cards they see; Home's card on a list is downloaded and
            // marked printed only by an admin over Home (a list with none of theirs is not found).
            'admin.card-requests.print-lists.store' => [AccessRowEnum::Cards, Actor::admins()],
            'admin.card-requests.print-lists.show' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'admin.card-requests.print-lists.mark-printed' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'admin.card-requests.mark-paid' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'admin.card-requests.approve' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'admin.card-requests.refuse' => [AccessRowEnum::Cards, Actor::editingFamily()],
            'admin.card-requests.mark-printed' => [AccessRowEnum::Cards, Actor::editingFamily()],
            // Answered from either family's page (there is no Duplicates page — DECISIONS 2026-10-17, after first look);
            // answering needs the new family.
            'admin.duplicates.resolve' => [AccessRowEnum::ReviewDuplicates, Actor::editingFamily()],
            // Moving-Up (DECISIONS 2026-11-08): per satellite — any admin of Home, for Home's children.
            'admin.moving-up.index' => [AccessRowEnum::MovingUp, Actor::admins()],
            'admin.moving-up.store' => [AccessRowEnum::MovingUp, Actor::admins()],
            'admin.moving-up.children.put-back' => [AccessRowEnum::MovingUp, Actor::admins()],
            'admin.moving-up.put-back' => [AccessRowEnum::MovingUp, Actor::admins()],
            // Classes (DECISIONS 2026-10-26): the page opens for every admin; Home's class is changed only by an admin
            // over Home.
            // A class admin (TODO #9) sees and changes their own class — the world's — and adds none.
            'admin.schedules.index' => [AccessRowEnum::ManageClasses, [...Actor::admins(), Actor::ClassAdmin]],
            'admin.schedules.store' => [AccessRowEnum::ManageClasses, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin]],
            'admin.schedules.update' => [AccessRowEnum::ManageClasses, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin, Actor::ClassAdmin]],
            'admin.schedules.retire' => [AccessRowEnum::ManageClasses, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin, Actor::ClassAdmin]],
            // Links (Roy 2026-11-06): the page opens for every admin; Home's Links are changed only by an admin over Home.
            'admin.links.index' => [AccessRowEnum::ManageLinks, Actor::admins()],
            'admin.links.store' => [AccessRowEnum::ManageLinks, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin]],
            'admin.links.update' => [AccessRowEnum::ManageLinks, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin]],
            'admin.links.move' => [AccessRowEnum::ManageLinks, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin]],
            'admin.links.destroy' => [AccessRowEnum::ManageLinks, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin]],
            // Attendance (DECISIONS 2026-10-26: reports are admins only): Home's class, only for an admin over Home.
            'admin.attendance.show' => [AccessRowEnum::Attendance, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin, Actor::ClassAdmin]],
            'admin.attendance.export' => [AccessRowEnum::Attendance, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin, Actor::ClassAdmin]],
            // People: adding or removing a home Lifeshaper needs an admin at Home.
            'admin.people.index' => [AccessRowEnum::GrantRoles, Actor::admins()],
            'admin.people.store' => [AccessRowEnum::GrantRoles, Actor::editingFamily()],
            'admin.people.roles.destroy' => [AccessRowEnum::GrantRoles, Actor::editingFamily()],
            // ── Serve: any staff ──
            'serve.poster' => [AccessRowEnum::StaffArea, Actor::staff()],
            // Serve's first page (spec items 16–17): every staff member, their own satellite's classes today.
            'serve.home' => [AccessRowEnum::CheckIn, Actor::staff()],
            // Opening and closing Home's class (spec item 15): an admin over Home, or the class's own class admin.
            'serve.sessions.open' => [AccessRowEnum::OpenSession, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin, Actor::ClassAdmin]],
            'session.close' => [AccessRowEnum::OpenSession, [Actor::HomeAndAwayAdmin, Actor::HomeAdmin, Actor::ClassAdmin]],
            // Home's 1PM door at its own address (spec item 16) and its Who's in (DECISIONS 2026-10-28): a Role covering
            // Home — anyone else is "not found" there.
            'session.checkin' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'session.check-ins.store' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'session.undo' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'session.whos-in' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'session.present' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
            'session.head-count' => [AccessRowEnum::CheckIn, Actor::coveringHome()],
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

    /**
     * What `$actor` gets on `$route`: `ok`, `404`, `login` (signed out), or `serve` — an Admin page opened by someone
     * who serves at Home without the admin Role it needs (a class admin has it for Classes and Attendance) is sent to
     * Home's Serve (spec item 10). Only a page is: any other act there is 404.
     */
    public static function expected(string $route, Actor $actor): string
    {
        [, $allowed] = self::rows()[$route];
        $classLevel = str_starts_with($route, 'admin.schedules.') || str_starts_with($route, 'admin.attendance.');
        $mayOpenAdmin = in_array($actor, [...Actor::admins(), ...($classLevel ? [Actor::ClassAdmin] : [])], true);
        // A page (GET) is named by what it shows; every other route is an act.
        $isPage = preg_match('/\.(index|show|export|register)$/', $route) === 1;

        return match (true) {
            $actor === Actor::Anonymous => 'login',
            in_array($actor, $allowed, true) => 'ok',
            str_starts_with($route, 'admin.') && $isPage && ! $mayOpenAdmin && in_array($actor, Actor::staff(), true) => 'serve',
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
