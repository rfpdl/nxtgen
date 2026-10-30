<?php

declare(strict_types=1);

namespace Tests\Feature\AccessMatrix;

use App\Data\Models\CardRequest;
use App\Data\Models\PrintList;
use App\Data\Models\SquadHeadCount;
use App\Enums\AccessRowEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Attributes\AccessRow;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\AccessWorld;
use Tests\Support\Actor;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;
use function Pest\Laravel\travelTo;
use function Tests\Support\childEdit;
use function Tests\Support\familyEdit;

/*
 * The access matrix (plan §3.2, TODO #18, SECURITY #4): EVERY staff and parent route × every kind of person who
 * might ask. One church, two satellites ("home" — where the family lives — and "away"), one family with a child
 * whose photo is on file (AccessWorld), and every Actor: anonymous, parents, each staff role and scope, both hats,
 * revoked roles, the other satellite's staff and another church's admin.
 *
 * Each cell is one request with a VALID payload, so a refusal is the access rule speaking, never validation.
 * The answers: `ok` (it works: 200, or a redirect with a success message), `404` (refused — the app never says
 * 403 on these routes, so it never confirms that something it will not show exists), `login` (signed out).
 *
 * The route list comes from the router: a new staff or parent route with no row here fails the first test, and a
 * row whose Feature declares a different access row fails the second. (Lucid routes name a controller method, not
 * the Feature, so the Feature is read from the method's `serve(...)`.) The `it` titles are written out, not built
 * from matrix(), because StoryTokenTest counts a story only in a literal test title.
 *
 * The open routes (sign-in, links, public registration) are OpenRouteAccessTest's.
 */

/**
 * Route name => the access row its Feature declares, and who gets `ok`. Every other signed-in actor gets 404;
 * anonymous is sent to sign in.
 *
 * @return array<string, array{0: AccessRowEnum, 1: list<Actor>}>
 */
function matrix(): array
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

/**
 * One route's row, as dataset cells: [route, actor, expected], named "actor → expected".
 *
 * @return array<string, array{0: string, 1: Actor, 2: string}>
 */
function cells(string $route): array
{
    [, $allowed] = matrix()[$route];
    $cells = [];
    foreach (Actor::cases() as $actor) {
        $expected = match (true) {
            $actor === Actor::Anonymous => 'login',
            in_array($actor, $allowed, true) => 'ok',
            default => '404',
        };
        $cells["{$actor->value} → {$expected}"] = [$route, $actor, $expected];
    }

    return $cells;
}

/**
 * The request a route's cell makes — method, URL and a payload that passes validation.
 *
 * @return array{0: string, 1: string, 2: array<string, mixed>}
 */
function requestFor(string $route, AccessWorld $w): array
{
    $f = $w->family->id;
    $c = $w->child->id;
    $photo = fn (): UploadedFile => new UploadedFile(base_path('tests/Fixtures/phone-photo-rotated-gps.jpg'), 'IMG_0001.jpg', 'image/jpeg', null, true);
    $newChild = ['first_name' => 'Baby', 'middle_name' => null, 'last_name' => 'Ramos', 'nickname' => 'Baby', 'gender' => 'girl', 'date_of_birth' => '2021-01-01', 'has_allergies' => false, 'allergies' => null];

    return match ($route) {
        'church.home' => ['GET', '/ccf', []],
        'church.poster' => ['GET', '/ccf/poster', []],
        'church.families.index' => ['GET', '/ccf/families', []],
        'church.families.export' => ['GET', '/ccf/families/export', []],
        'church.children.index' => ['GET', '/ccf/children', []],
        'church.families.register' => ['GET', '/ccf/families/new?q=Amara', []],
        'church.families.store' => ['POST', '/ccf/families', [
            'home_satellite_id' => $w->home->id, 'area_text' => 'Deira', 'privacy_consented' => true,
            'guardian' => ['first_name' => 'Mira', 'last_name' => 'Bonza', 'relationship' => 'mother', 'phone' => '0523508443', 'email' => 'mira@example.com'],
            'children' => [['first_name' => 'Clarion', 'middle_name' => null, 'last_name' => 'Bonza', 'nickname' => 'Clarion', 'gender' => 'boy', 'date_of_birth' => '2018-02-03', 'has_allergies' => false, 'allergies' => null]],
        ]],
        'church.families.children.store' => ['POST', "/ccf/families/{$f}/children", $newChild],
        'church.families.show' => ['GET', "/ccf/families/{$f}", []],
        'church.families.update' => ['PUT', "/ccf/families/{$f}", familyEdit($w->home)],
        'church.families.resend' => ['POST', "/ccf/families/{$f}/resend", []],
        'church.families.email' => ['PUT', "/ccf/families/{$f}/email", ['email' => 'corrected@example.com']],
        'church.children.update' => ['PUT', "/ccf/children/{$c}", childEdit()],
        'church.children.status' => ['PUT', "/ccf/children/{$c}/status", ['status' => 'inactive']],
        'church.children.level' => ['PUT', "/ccf/children/{$c}/level", ['level' => 3]],
        'church.children.photo-consent' => ['PUT', "/ccf/children/{$c}/photo-consent", []],
        'church.children.photo' => ['POST', "/ccf/children/{$c}/photo", ['photo' => $photo()]],
        'church.card-requests.store' => ['POST', "/ccf/children/{$c}/card-requests", ['reason' => 'lost', 'note' => 'Lost at the park']],
        'church.card-requests.index' => ['GET', '/ccf/card-requests?tab=asked', []],
        'church.card-requests.print-lists.store' => ['POST', withApprovedCard($w, '/ccf/card-requests/print-lists'), ['q' => null]],
        'church.card-requests.print-lists.show' => ['GET', '/ccf/card-requests/print-lists/'.printListOf($w)->id, []],
        'church.card-requests.print-lists.mark-printed' => ['PUT', '/ccf/card-requests/print-lists/'.printListOf($w)->id.'/mark-printed', []],
        'church.card-requests.mark-paid' => ['PUT', '/ccf/card-requests/'.cardAt($w, CardStepEnum::Asked, paid: false)->id.'/mark-paid', []],
        'church.card-requests.approve' => ['PUT', '/ccf/card-requests/'.cardAt($w, CardStepEnum::Asked)->id.'/approve', []],
        'church.card-requests.refuse' => ['PUT', '/ccf/card-requests/'.cardAt($w, CardStepEnum::Asked)->id.'/refuse', ['reason' => 'Found it at home']],
        'church.card-requests.mark-printed' => ['PUT', '/ccf/card-requests/'.cardAt($w, CardStepEnum::Approved)->id.'/mark-printed', []],
        'church.card-requests.give' => ['PUT', '/ccf/card-requests/'.cardAt($w, CardStepEnum::Printed)->id.'/give', []],
        'church.duplicates.resolve' => ['PUT', "/ccf/duplicates/{$w->pair->id}", ['status' => 'dismissed']],
        'church.people.index' => ['GET', '/ccf/people', []],
        'church.people.store' => ['POST', '/ccf/people', ['name' => 'Leo Lifeshaper', 'email' => 'leo@example.com', 'role' => 'lifeshaper', 'scope_type' => 'satellite', 'scope_id' => $w->home->id]],
        'church.people.roles.destroy' => ['DELETE', "/ccf/people/roles/{$w->assignment->id}", []],
        'church.schedules.index' => ['GET', '/ccf/classes', []],
        'church.schedules.store' => ['POST', '/ccf/classes', ['satellite_id' => $w->home->id, 'name' => 'Special class', 'weekday' => null, 'starts_at' => null, 'ends_at' => null]],
        'church.schedules.update' => ['PUT', "/ccf/classes/{$w->schedule->id}", ['name' => '1PM', 'weekday' => 'saturday', 'starts_at' => '13:30', 'ends_at' => '15:00']],
        'church.schedules.retire' => ['PUT', "/ccf/classes/{$w->schedule->id}/retire", []],
        'church.door.show' => ['GET', '/ccf/door?q=Amara', []],
        'church.door.check-in' => ['POST', "/ccf/classes/{$w->schedule->id}/check-ins", ['children' => [['child_id' => $w->visitor->id, 'level' => 1, 'make_active' => false]]]],
        'church.door.undo' => ['PUT', "/ccf/check-ins/{$w->checkIn->id}/undo", []],
        'church.door.whos-in' => ['GET', '/ccf/door/whos-in', []],
        'church.door.present' => ['PUT', "/ccf/check-ins/{$w->checkIn->id}/present", ['present' => true, 'revision' => $w->checkIn->revision]],
        'church.door.head-count' => ['PUT', "/ccf/classes/{$w->schedule->id}/squads/{$w->checkIn->squad_id}/head-count", ['count' => 3]],
        'church.attendance.show' => ['GET', "/ccf/attendance?class={$w->schedule->id}&date=2026-10-31", []],
        'church.attendance.export' => ['GET', "/ccf/attendance/export?class={$w->schedule->id}&date=2026-10-31", []],
        'family.show' => ['GET', '/ccf/family', []],
        'family.update' => ['PUT', '/ccf/family', familyEdit($w->home)],
        'family.children.store' => ['POST', '/ccf/family/children', $newChild],
        'family.children.update' => ['PUT', "/ccf/family/children/{$c}", childEdit()],
        'family.children.photo' => ['POST', "/ccf/family/children/{$c}/photo", ['photo' => $photo(), 'consent' => true]],
        'family.children.photo-consent.give' => ['PUT', "/ccf/family/children/{$c}/photo-consent", []],
        'family.children.photo-consent' => ['DELETE', "/ccf/family/children/{$c}/photo-consent", []],
        'photos.child' => ['GET', URL::temporarySignedRoute('photos.child', now()->addMinutes(30), ['child' => $c]), []],
        default => throw new LogicException("No request for {$route} — add it to requestFor()."),
    };
}

/**
 * The world's child's one open Card request, put at `$step` (paid unless said otherwise) — made the first time, moved
 * after, so one world can serve every card route (the other-church guesses run them all on one world).
 */
function cardAt(AccessWorld $w, CardStepEnum $step, bool $paid = true): CardRequest
{
    $request = CardRequest::query()->where('child_id', $w->child->id)->first() ?? CardRequest::factory()->for($w->child)->create();
    $request->forceFill(['step' => $step, 'paid_at' => $paid ? now() : null, 'approved_at' => $step === CardStepEnum::Asked ? null : now()])->save();

    return $request;
}

/** `$uri`, once the world's child has an approved card ("Make print list" names no request). */
function withApprovedCard(AccessWorld $w, string $uri): string
{
    cardAt($w, CardStepEnum::Approved);

    return $uri;
}

/** A print list holding the world's child's approved card (made once, so one world serves every guess). */
function printListOf(AccessWorld $w): PrintList
{
    $card = cardAt($w, CardStepEnum::Approved);
    $list = PrintList::query()->where('church_id', $w->church->id)->first() ?? PrintList::factory()->for($w->church)->create();
    $list->cards()->syncWithoutDetaching([$card->id]);

    return $list;
}

/**
 * The changes that say nothing when they work, and what proves each one happened instead. A tap on Who's in marks a
 * child present, and a head count is confirmed, with no message (DECISIONS 2026-10-28): a band appearing at the top
 * would push the list down under the next tap; the Squad's own line says it.
 *
 * @return array<string, Closure(AccessWorld): bool>
 */
function quietChanges(): array
{
    return [
        'church.door.present' => fn (AccessWorld $w): bool => $w->checkIn->fresh()?->isPresent() === true,
        'church.door.head-count' => fn (AccessWorld $w): bool => SquadHeadCount::query()->where('session_id', $w->checkIn->session_id)->where('squad_id', $w->checkIn->squad_id)->value('head_count') === 3,
    ];
}

/**
 * What a response says, in the matrix's words. `$worked` is a quiet change's proof (quietChanges()): when given, it
 * alone decides whether a redirect worked.
 *
 * @param  TestResponse<Response>  $response
 */
function outcomeOf(TestResponse $response, string $method, ?bool $worked = null): string
{
    $status = $response->getStatusCode();
    $path = rtrim((string) parse_url((string) $response->headers->get('Location'), PHP_URL_PATH), '/');

    if ($status === 302 && $path === '/login') {
        return 'login';
    }
    if ($status === 404) {
        return '404';
    }
    if ($status === 200 && $method === 'GET') {
        return 'ok';
    }

    // A redirect is `ok` only without errors — and, for a change, only when it says it worked; for a quiet change,
    // only when its proof holds, whatever it says.
    $failed = session()->has('errors') || session()->has('error');
    $saidSo = $worked ?? ($method === 'GET' || session()->has('success'));
    if ($status === 302 && ! $failed && $saidSo) {
        return 'ok';
    }

    $error = session('error');

    return "{$status}".(session()->has('errors') ? ' (validation errors)' : '').(is_string($error) ? " (error: {$error})" : '');
}

/** The one test body every row's cells share. */
function cellTest(): Closure
{
    return fn (string $route, Actor $actor, string $expected) => expectCell($route, $actor, $expected);
}

function expectCell(string $route, Actor $actor, string $expected): void
{
    Notification::fake();
    Storage::fake('photos');
    // The door is asked about on the Saturday its classes meet.
    if (str_starts_with($route, 'church.door.')) {
        travelTo(CarbonImmutable::parse(AccessWorld::SATURDAY, 'Asia/Dubai'));
    }
    $world = AccessWorld::make();
    [$method, $uri, $payload] = requestFor($route, $world);

    $user = $world->user($actor);
    if ($user !== null) {
        actingAs($user);
    }

    $response = call($method, $uri, $payload);
    $proof = quietChanges()[$route] ?? null;
    expect(outcomeOf($response, $method, $proof === null ? null : $proof($world)))->toBe($expected, "{$actor->value} on {$route}");
}

/** @return list<Route> the staff and parent routes: served by the Church and Family services */
function scopedRoutes(): array
{
    return array_values(array_filter(
        Router::getRoutes()->getRoutes(),
        fn (Route $r): bool => str_starts_with($r->getControllerClass() ?? '', 'App\\Services\\Church\\')
            || str_starts_with($r->getControllerClass() ?? '', 'App\\Services\\Family\\'),
    ));
}

/** The Feature a route's controller action serves, read from the action's source. */
function featureOf(Route $route): string
{
    $method = new ReflectionMethod((string) $route->getControllerClass(), $route->getActionMethod());
    $file = (string) $method->getFileName();
    $lines = array_slice((array) file($file), $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);
    preg_match('/serve\((\w+)::class/', implode('', array_map(strval(...), $lines)), $served);
    preg_match('/^use ([\w\\\\]+\\\\'.($served[1] ?? '-').');$/m', (string) file_get_contents($file), $import);

    return $import[1] ?? '';
}

it('has a row for every staff and parent route, and no row for a route that is gone', function (): void {
    $names = array_map(fn (Route $r): string => (string) $r->getName(), scopedRoutes());
    sort($names);
    $rows = array_keys(matrix());
    sort($rows);

    expect(count($names))->toBeGreaterThan(25)
        ->and($names)->toBe($rows, 'A new staff or parent route needs its row in matrix() and requestFor()');
});

it('lists, for every route, the access row its Feature declares', function (): void {
    $wrong = [];
    foreach (scopedRoutes() as $route) {
        $feature = featureOf($route);
        $declared = class_exists($feature) ? array_map(
            fn (ReflectionAttribute $a): AccessRowEnum => $a->newInstance()->row,
            (new ReflectionClass($feature))->getAttributes(AccessRow::class),
        ) : [];
        if ($declared !== [matrix()[(string) $route->getName()][0]]) {
            $wrong[] = "{$route->getName()} → {$feature}";
        }
    }

    expect($wrong)->toBe([]);
});

describe('the staff area', function (): void {
    it('opens the church home for any staff @story#42', cellTest())
        ->with(cells('church.home'));
    it('gives any staff the poster, and nobody else @story#80 @story#81', cellTest())
        ->with(cells('church.poster'));
});

describe('the registration lists', function (): void {
    it('shows the families list to any staff, and nothing to anyone else @story#43 @story#44', cellTest())
        ->with(cells('church.families.index'));
    it('downloads the families list for any staff @story#45', cellTest())
        ->with(cells('church.families.export'));
    it('shows the children list to any staff, and nothing to anyone else @story#58 @story#59', cellTest())
        ->with(cells('church.children.index'));
});

describe('the door: search, register, add a child', function (): void {
    it('lets any staff search the whole church @story#46', cellTest())
        ->with(cells('church.families.register'));
    it('lets any staff register a family @story#47', cellTest())
        ->with(cells('church.families.store'));
    it('lets any staff add a child to a family found, at any satellite @story#48 @story#49', cellTest())
        ->with(cells('church.families.children.store'));
});

describe('a family, for staff', function (): void {
    it('opens for staff who can see its satellite @story#50 @story#51', cellTest())
        ->with(cells('church.families.show'));
    it('is edited only by an admin who may edit it @story#52 @story#53', cellTest())
        ->with(cells('church.families.update'));
    it('has its link resent by any staff member in the church @story#54 @story#55', cellTest())
        ->with(cells('church.families.resend'));
    it('has its email corrected only by an admin who may edit it @story#56 @story#57', cellTest())
        ->with(cells('church.families.email'));
});

describe('a child, for staff', function (): void {
    it('has its details fixed only by an admin who may edit the family @story#60 @story#61', cellTest())
        ->with(cells('church.children.update'));
    it('has its status set only by an admin who may edit the family @story#62 @story#63', cellTest())
        ->with(cells('church.children.status'));
    it('has its Level corrected only by a church admin @story#64 @story#65', cellTest())
        ->with(cells('church.children.level'));
    it('has in-person consent recorded by staff who can see the family @story#66 @story#67', cellTest())
        ->with(cells('church.children.photo-consent'));
    it('has its photo taken by staff who can see the family @story#68 @story#69', cellTest())
        ->with(cells('church.children.photo'));
    it('has a new name card asked for by staff who can see the family, never by a parent @story#130 @story#131', cellTest())
        ->with(cells('church.card-requests.store'));
    it('has a printed card given at the desk by staff who can see the family @story#146 @story#147', cellTest())
        ->with(cells('church.card-requests.give'));
});

describe('Cards, for admins', function (): void {
    it('opens Cards for admins only @story#134 @story#135', cellTest())
        ->with(cells('church.card-requests.index'));
    it('lets any admin make a print list of the approved cards they see @story#150 @story#151', cellTest())
        ->with(cells('church.card-requests.print-lists.store'));
    it('downloads a print list holding a Home card only for an admin over Home @story#148 @story#149', cellTest())
        ->with(cells('church.card-requests.print-lists.show'));
    it('marks a print list holding a Home card printed only for an admin over Home @story#144 @story#145', cellTest())
        ->with(cells('church.card-requests.print-lists.mark-printed'));
    it('has a Home card marked paid only by an admin over Home @story#136 @story#137', cellTest())
        ->with(cells('church.card-requests.mark-paid'));
    it('has a Home card approved only by an admin over Home @story#138 @story#139', cellTest())
        ->with(cells('church.card-requests.approve'));
    it('has a Home card refused only by an admin over Home @story#140 @story#141', cellTest())
        ->with(cells('church.card-requests.refuse'));
    it('has a Home card marked printed only by an admin over Home @story#142 @story#143', cellTest())
        ->with(cells('church.card-requests.mark-printed'));
});

describe('duplicates and People, for admins', function (): void {
    it('lets a pair be answered only by an admin of the new family @story#72 @story#73', cellTest())
        ->with(cells('church.duplicates.resolve'));
    it('opens People for admins only @story#74 @story#75', cellTest())
        ->with(cells('church.people.index'));
    it('lets a home Lifeshaper be added only by an admin over that satellite @story#76 @story#77', cellTest())
        ->with(cells('church.people.store'));
    it('lets a home Lifeshaper\'s role be removed only by an admin over that satellite @story#78 @story#79', cellTest())
        ->with(cells('church.people.roles.destroy'));
});

describe('Classes, for admins', function (): void {
    it('opens Classes for admins only @story#95 @story#96', cellTest())
        ->with(cells('church.schedules.index'));
    it('lets a class be added at Home only by an admin over Home @story#97 @story#98', cellTest())
        ->with(cells('church.schedules.store'));
    it('lets a Home class be edited only by an admin over Home @story#99 @story#100', cellTest())
        ->with(cells('church.schedules.update'));
    it('lets a Home class be retired only by an admin over Home @story#101 @story#102', cellTest())
        ->with(cells('church.schedules.retire'));
});

describe('the door, for staff', function (): void {
    it('opens the door for any staff, with their own satellites\' classes @story#103 @story#104', cellTest())
        ->with(cells('church.door.show'));
    it('lets children be checked in at Home\'s class only by staff whose Role covers Home @story#106 @story#109', cellTest())
        ->with(cells('church.door.check-in'));
    it('lets a Check-in at Home be undone only by staff whose Role covers Home @story#110 @story#111', cellTest())
        ->with(cells('church.door.undo'));
    it('opens Who\'s in for any staff, with their own satellites\' classes @story#117 @story#118', cellTest())
        ->with(cells('church.door.whos-in'));
    it('lets a child at Home\'s class be marked present only by staff whose Role covers Home @story#119 @story#120', cellTest())
        ->with(cells('church.door.present'));
    it('lets a head count at Home\'s class be confirmed only by staff whose Role covers Home @story#121 @story#123', cellTest())
        ->with(cells('church.door.head-count'));
});

describe('Attendance, for admins', function (): void {
    it('shows Home\'s Session only to an admin over Home @story#112 @story#113', cellTest())
        ->with(cells('church.attendance.show'));
    it('downloads Home\'s Session only for an admin over Home @story#114 @story#115', cellTest())
        ->with(cells('church.attendance.export'));
});

describe('the parent\'s own family', function (): void {
    it('opens a parent\'s own family page, for parents only @story#28 @story#29', cellTest())
        ->with(cells('family.show'));
    it('lets a parent edit their own family @story#30', cellTest())
        ->with(cells('family.update'));
    it('lets a parent add a child to their own family @story#32', cellTest())
        ->with(cells('family.children.store'));
    it('lets only the child\'s own parent edit the child @story#34 @story#35', cellTest())
        ->with(cells('family.children.update'));
    it('lets only the child\'s own parent add a photo @story#38 @story#39', cellTest())
        ->with(cells('family.children.photo'));
    it('lets only the child\'s own parent agree to photos @story#36', cellTest())
        ->with(cells('family.children.photo-consent.give'));
    it('lets only the child\'s own parent take consent back @story#37', cellTest())
        ->with(cells('family.children.photo-consent'));
});

it('shows a child\'s photo to their family and staff who can see the family, and nobody else @story#40', cellTest())
    ->with(cells('photos.child'));

/** @return list<string> the ccf routes whose address names a family, child, pair, role, class or Check-in */
function routesNamingAnId(): array
{
    return array_values(array_filter(array_keys(matrix()), function (string $route): bool {
        $uri = (string) Router::getRoutes()->getByName($route)?->uri();

        return str_starts_with($uri, '{church}') && preg_match('/\{(family|child|pair|assignment|schedule|checkIn|cardRequest|printList)\}/', $uri) === 1;
    }));
}

// Every refusal is a 404 and changes nothing, so one world serves all thirty guesses.
it('refuses another church\'s admin (a parent there too) who puts ccf\'s ids in their OWN church\'s address @story#29 @story#49 @story#51 @story#100 @story#102 @story#109 @story#111 @story#120 @story#123 @story#131 @story#137 @story#139 @story#141 @story#143 @story#145 @story#147 @story#149', function (): void {
    Notification::fake();
    Storage::fake('photos');
    travelTo(CarbonImmutable::parse(AccessWorld::SATURDAY, 'Asia/Dubai'));
    $world = AccessWorld::make();
    actingAs($world->user(Actor::OtherChurch) ?? throw new LogicException('The other church has an admin.'));

    $answers = [];
    foreach (routesNamingAnId() as $route) {
        [$method, $uri, $payload] = requestFor($route, $world);
        // A payload valid in THEIR church (a satellite of the other church), so only the id can refuse it.
        if (isset($payload['home_satellite_id'])) {
            $payload = familyEdit($world->otherChurch->satellites()->firstOrFail());
        }
        $guessed = '/other'.substr($uri, strlen('/ccf'));
        $answers["{$method} {$guessed}"] = outcomeOf(call($method, $guessed, $payload), $method);
    }

    expect($answers)->toHaveCount(30)
        ->and(array_unique(array_values($answers)))->toBe(['404'], (string) json_encode($answers, JSON_PRETTY_PRINT));
});
