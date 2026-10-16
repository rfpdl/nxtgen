<?php

declare(strict_types=1);

namespace Tests\Feature\AccessMatrix;

use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
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
        // Any Lifeshaper, wherever they serve: they meet the parent at the door (plan §3.2, read literally) — so an away
        // Lifeshaper may and an away satellite admin may not. To confirm with Roy (TODO #28).
        'church.families.resend' => [AccessRowEnum::ResendLink, [...Actor::seeingFamily(), Actor::AwayLifeshaper]],
        'church.families.email' => [AccessRowEnum::CorrectContact, Actor::editingFamily()],
        'church.children.status' => [AccessRowEnum::SetChildStatus, Actor::editingFamily()],
        'church.children.level' => [AccessRowEnum::SetChildLevel, [Actor::ChurchAdmin]],
        'church.children.photo-consent' => [AccessRowEnum::ChildPhoto, Actor::seeingFamily()],
        'church.children.photo' => [AccessRowEnum::ChildPhoto, Actor::seeingFamily()],
        // The page opens for every admin (the away admin sees an empty list); answering a pair needs the new family.
        'church.duplicates.index' => [AccessRowEnum::ReviewDuplicates, [Actor::ChurchAdmin, Actor::HomeAdmin, Actor::AwayAdmin]],
        'church.duplicates.resolve' => [AccessRowEnum::ReviewDuplicates, Actor::editingFamily()],
        // People opens for every admin; adding or removing a home Lifeshaper needs the home satellite.
        'church.people.index' => [AccessRowEnum::GrantRoles, [Actor::ChurchAdmin, Actor::HomeAdmin, Actor::AwayAdmin]],
        'church.people.store' => [AccessRowEnum::GrantRoles, Actor::editingFamily()],
        'church.people.roles.destroy' => [AccessRowEnum::GrantRoles, Actor::editingFamily()],
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
        'church.duplicates.index' => ['GET', '/ccf/duplicates', []],
        'church.duplicates.resolve' => ['PUT', "/ccf/duplicates/{$w->pair->id}", ['status' => 'dismissed']],
        'church.people.index' => ['GET', '/ccf/people', []],
        'church.people.store' => ['POST', '/ccf/people', ['name' => 'Leo Lifeshaper', 'email' => 'leo@example.com', 'role' => 'lifeshaper', 'scope_type' => 'satellite', 'scope_id' => $w->home->id]],
        'church.people.roles.destroy' => ['DELETE', "/ccf/people/roles/{$w->assignment->id}", []],
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
 * What a response says, in the matrix's words.
 *
 * @param  TestResponse<Response>  $response
 */
function outcomeOf(TestResponse $response, string $method): string
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

    // A redirect is `ok` only without errors — and, for a change, only when it says it worked.
    $failed = session()->has('errors') || session()->has('error');
    if ($status === 302 && ! $failed && ($method === 'GET' || session()->has('success'))) {
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
    $world = AccessWorld::make();
    [$method, $uri, $payload] = requestFor($route, $world);

    $user = $world->user($actor);
    if ($user !== null) {
        actingAs($user);
    }

    expect(outcomeOf(call($method, $uri, $payload), $method))->toBe($expected, "{$actor->value} on {$route}");
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
    it('has its link resent by its admins or any Lifeshaper @story#54 @story#55', cellTest())
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
});

describe('duplicates and People, for admins', function (): void {
    it('opens possible duplicates for admins only @story#70 @story#71', cellTest())
        ->with(cells('church.duplicates.index'));
    it('lets a pair be answered only by an admin of the new family @story#72 @story#73', cellTest())
        ->with(cells('church.duplicates.resolve'));
    it('opens People for admins only @story#74 @story#75', cellTest())
        ->with(cells('church.people.index'));
    it('lets a home Lifeshaper be added only by an admin over that satellite @story#76 @story#77', cellTest())
        ->with(cells('church.people.store'));
    it('lets a home Lifeshaper\'s role be removed only by an admin over that satellite @story#78 @story#79', cellTest())
        ->with(cells('church.people.roles.destroy'));
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

/** @return list<string> the ccf routes whose address names a family, child, pair or role */
function routesNamingAnId(): array
{
    return array_values(array_filter(array_keys(matrix()), function (string $route): bool {
        $uri = (string) Router::getRoutes()->getByName($route)?->uri();

        return str_starts_with($uri, '{church}') && preg_match('/\{(family|child|pair|assignment)\}/', $uri) === 1;
    }));
}

// Every refusal is a 404 and changes nothing, so one world serves all sixteen guesses.
it('refuses another church\'s admin (a parent there too) who puts ccf\'s ids in their OWN church\'s address @story#29 @story#49 @story#51', function (): void {
    Notification::fake();
    Storage::fake('photos');
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

    expect($answers)->toHaveCount(16)
        ->and(array_unique(array_values($answers)))->toBe(['404'], (string) json_encode($answers, JSON_PRETTY_PRINT));
});
