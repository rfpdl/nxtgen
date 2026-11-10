<?php

declare(strict_types=1);

namespace Tests\Feature\AccessMatrix;

use App\Data\Models\CardRequest;
use App\Data\Models\MovingUp;
use App\Data\Models\PrintList;
use App\Data\Models\SatelliteLink;
use App\Data\Models\SquadHeadCount;
use App\Enums\AccessRowEnum;
use App\Enums\CardStepEnum;
use App\Enums\ChildStatusEnum;
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
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\AccessMatrix;
use Tests\Support\AccessWorld;
use Tests\Support\Actor;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;
use function Pest\Laravel\travelTo;
use function Tests\Support\childEdit;
use function Tests\Support\familyEdit;

/*
 * The access matrix (plan §3.2, TODO #18, SECURITY #4): EVERY staff and parent route × every kind of person who
 * might ask. One church, two satellites ("home" — where the family lives, and the satellite in every staff address
 * asked: `/home-sat/…` — and "away"), one family with a child whose photo is on file (AccessWorld), and every Actor:
 * anonymous, parents, each staff role and scope, both hats, revoked roles, the other satellite's staff and another
 * church's admin.
 *
 * Each cell is one request with a VALID payload, so a refusal is the access rule speaking, never validation.
 * The answers: `ok` (it works: 200, or a redirect with a success message), `404` (refused — the app never says
 * 403 on these routes, so it never confirms that something it will not show exists), `login` (signed out), `serve`
 * (an Admin page opened by someone who only serves here: sent to Home's Serve — spec item 10).
 *
 * The route list comes from the router: a new staff or parent route with no row here fails the first test, and a
 * row whose Feature declares a different access row fails the second. (Lucid routes name a controller method, not
 * the Feature, so the Feature is read from the method's `serve(...)`.) The `it` titles are written out, not built
 * from matrix(), because StoryTokenTest counts a story only in a literal test title.
 *
 * The open routes (sign-in, links, public registration) are OpenRouteAccessTest's.
 */

/**
 * Route name => the access row its Feature declares, and who gets `ok` (AccessMatrix — the test map shows the same).
 *
 * @return array<string, array{0: AccessRowEnum, 1: list<Actor>}>
 */
function matrix(): array
{
    return AccessMatrix::rows();
}

/**
 * One route's row, as dataset cells: [route, actor, expected], named "actor → expected".
 *
 * @return array<string, array{0: string, 1: Actor, 2: string}>
 */
function cells(string $route): array
{
    $cells = [];
    foreach (Actor::cases() as $actor) {
        $expected = AccessMatrix::expected($route, $actor);
        $cells["{$actor->value} → {$expected}"] = [$route, $actor, $expected];
    }

    return $cells;
}

/**
 * A family page's cells in both areas (Roy 2026-11-06): Serve's, then Admin's — the same row, admins only.
 *
 * @return array<string, array{0: string, 1: Actor, 2: string}>
 */
function bothAreas(string $name): array
{
    $cells = [];
    foreach (['serve', 'admin'] as $area) {
        foreach (cells("{$area}.{$name}") as $key => $cell) {
            $cells["{$area}: {$key}"] = $cell;
        }
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

    if ($route === 'satellite.home') {
        return ['GET', '/home-sat', []];
    }
    if (str_starts_with($route, 'family.') || $route === 'photos.child') {
        return familyRequestFor($route, $w, $c, $photo, $newChild);
    }
    // The staff areas (Roy 2026-11-06): the same request under each area's address, at Home.
    [$base, $name] = match (true) {
        str_starts_with($route, 'admin.') => ['/home-sat/admin', substr($route, 6)],
        str_starts_with($route, 'serve.') => ['/home-sat/serve', substr($route, 6)],
        default => throw new LogicException("No request for {$route} — add it to requestFor()."),
    };

    return match ($name) {
        'poster' => ['GET', "{$base}/poster", []],
        'families.index' => ['GET', "{$base}/families", []],
        'families.export' => ['GET', "{$base}/families/export", []],
        'children.index' => ['GET', "{$base}/children", []],
        'families.register' => ['GET', "{$base}/families/new?q=Amara", []],
        'families.store' => ['POST', "{$base}/families", [
            'home_satellite_id' => $w->home->id, 'area_text' => 'Deira', 'privacy_consented' => true,
            'guardian' => ['first_name' => 'Mira', 'last_name' => 'Bonza', 'relationship' => 'mother', 'phone' => '0523508443', 'email' => 'mira@example.com'],
            'children' => [['first_name' => 'Clarion', 'middle_name' => null, 'last_name' => 'Bonza', 'nickname' => 'Clarion', 'gender' => 'boy', 'date_of_birth' => '2018-02-03', 'has_allergies' => false, 'allergies' => null]],
        ]],
        'families.children.store' => ['POST', "{$base}/families/{$f}/children", $newChild],
        'families.show' => ['GET', "{$base}/families/{$f}", []],
        'families.update' => ['PUT', "{$base}/families/{$f}", familyEdit($w->home)],
        'families.resend' => ['POST', "{$base}/families/{$f}/resend", []],
        'families.email' => ['PUT', "{$base}/families/{$f}/email", ['email' => 'corrected@example.com']],
        'children.update' => ['PUT', "{$base}/children/{$c}", childEdit()],
        'children.status' => ['PUT', "{$base}/children/{$c}/status", ['status' => 'inactive']],
        'children.level' => ['PUT', "{$base}/children/{$c}/level", ['level' => 3]],
        'children.photo-consent' => ['PUT', "{$base}/children/{$c}/photo-consent", []],
        'children.photo' => ['POST', "{$base}/children/{$c}/photo", ['photo' => $photo()]],
        'children.photo.destroy' => ['DELETE', "{$base}/children/{$c}/photo", []],
        'card-requests.store' => ['POST', "{$base}/children/{$c}/card-requests", ['reason' => 'lost', 'note' => 'Lost at the park']],
        'card-requests.index' => ['GET', "{$base}/card-requests?tab=asked", []],
        'card-requests.print-lists.store' => ['POST', withApprovedCard($w, "{$base}/card-requests/print-lists"), ['q' => null]],
        'card-requests.print-lists.show' => ['GET', "{$base}/card-requests/print-lists/".printListOf($w)->id, []],
        'card-requests.print-lists.mark-printed' => ['PUT', "{$base}/card-requests/print-lists/".printListOf($w)->id.'/mark-printed', []],
        'card-requests.mark-paid' => ['PUT', "{$base}/card-requests/".cardAt($w, CardStepEnum::Asked, paid: false)->id.'/mark-paid', []],
        'card-requests.approve' => ['PUT', "{$base}/card-requests/".cardAt($w, CardStepEnum::Asked)->id.'/approve', []],
        'card-requests.refuse' => ['PUT', "{$base}/card-requests/".cardAt($w, CardStepEnum::Asked)->id.'/refuse', ['reason' => 'Found it at home']],
        'card-requests.mark-printed' => ['PUT', "{$base}/card-requests/".cardAt($w, CardStepEnum::Approved)->id.'/mark-printed', []],
        'card-requests.give' => ['PUT', "{$base}/card-requests/".cardAt($w, CardStepEnum::Printed)->id.'/give', []],
        'duplicates.resolve' => ['PUT', "{$base}/duplicates/{$w->pair->id}", ['status' => 'dismissed']],
        'people.index' => ['GET', "{$base}/people", []],
        'people.store' => ['POST', "{$base}/people", ['name' => 'Leo Lifeshaper', 'email' => 'leo@example.com', 'role' => 'lifeshaper', 'scope_type' => 'satellite', 'scope_id' => $w->home->id]],
        'people.roles.destroy' => ['DELETE', "{$base}/people/roles/{$w->assignment->id}", []],
        'moving-up.index' => ['GET', "{$base}/moving-up", []],
        'moving-up.store' => ['POST', "{$base}/moving-up", ['children' => [['child_id' => $c, 'level' => childAtLevel($w)]]]],
        'moving-up.children.put-back' => ['PUT', "{$base}/moving-up/".movingUpOf($w)->id."/children/{$c}/put-back", []],
        'moving-up.put-back' => ['PUT', "{$base}/moving-up/".movingUpOf($w)->id.'/put-back', []],
        'links.index' => ['GET', "{$base}/links", []],
        'links.store' => ['POST', "{$base}/links", ['satellite_id' => $w->home->id, 'label' => 'Parents\' WhatsApp group', 'url' => 'https://chat.whatsapp.com/abc']],
        'links.update' => ['PUT', "{$base}/links/".linkOf($w)->id, ['label' => 'Facebook group', 'url' => 'https://facebook.com/groups/ccf']],
        'links.move' => ['PUT', "{$base}/links/".linkOf($w)->id.'/move', ['direction' => 'up']],
        'links.destroy' => ['DELETE', "{$base}/links/".linkOf($w)->id, []],
        'schedules.index' => ['GET', "{$base}/classes", []],
        'schedules.store' => ['POST', "{$base}/classes", ['satellite_id' => $w->home->id, 'name' => 'Special class', 'one_time' => true, 'weekday' => null, 'date' => '2026-12-24', 'starts_at' => '18:00', 'ends_at' => '20:00']],
        'schedules.update' => ['PUT', "{$base}/classes/{$w->schedule->id}", ['name' => '1PM', 'one_time' => false, 'weekday' => 'saturday', 'date' => null, 'starts_at' => '13:30', 'ends_at' => '15:00']],
        'schedules.retire' => ['PUT', "{$base}/classes/{$w->schedule->id}/retire", []],
        'door.show' => ['GET', "{$base}/door?q=Amara", []],
        'door.check-in' => ['POST', "{$base}/classes/{$w->schedule->id}/check-ins", ['children' => [['child_id' => $w->visitor->id, 'level' => 1, 'make_active' => false]]]],
        'door.undo' => ['PUT', "{$base}/check-ins/{$w->checkIn->id}/undo", []],
        'door.whos-in' => ['GET', "{$base}/door/whos-in", []],
        'door.present' => ['PUT', "{$base}/check-ins/{$w->checkIn->id}/present", ['present' => true, 'revision' => $w->checkIn->revision]],
        'door.head-count' => ['PUT', "{$base}/classes/{$w->schedule->id}/squads/{$w->checkIn->squad_id}/head-count", ['count' => 3]],
        'attendance.show' => ['GET', "{$base}/attendance?class={$w->schedule->id}&date=2026-10-31", []],
        'attendance.export' => ['GET', "{$base}/attendance/export?class={$w->schedule->id}&date=2026-10-31", []],
        default => throw new LogicException("No request for {$route} — add it to requestFor()."),
    };
}

/**
 * A parent's request, or a photo's.
 *
 * @param  Closure(): UploadedFile  $photo
 * @param  array<string, mixed>  $newChild
 * @return array{0: string, 1: string, 2: array<string, mixed>}
 */
function familyRequestFor(string $route, AccessWorld $w, int $c, Closure $photo, array $newChild): array
{
    return match ($route) {
        'family.show' => ['GET', '/family', []],
        'family.update' => ['PUT', '/family', familyEdit($w->home)],
        'family.children.store' => ['POST', '/family/children', $newChild],
        'family.children.update' => ['PUT', "/family/children/{$c}", childEdit()],
        'family.children.photo' => ['POST', "/family/children/{$c}/photo", ['photo' => $photo(), 'consent' => true]],
        'family.children.photo-consent.give' => ['PUT', "/family/children/{$c}/photo-consent", []],
        'family.children.photo-consent' => ['DELETE', "/family/children/{$c}/photo-consent", []],
        'family.children.photo.destroy' => ['DELETE', "/family/children/{$c}/photo", []],
        'photos.child' => ['GET', URL::temporarySignedRoute('photos.child', now()->addMinutes(30), ['child' => $c]), []],
        default => throw new LogicException("No request for {$route} — add it to familyRequestFor()."),
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

/** The world's child at a Level (it may have none), to be moved up from it. */
function childAtLevel(AccessWorld $w): int
{
    $w->child->forceFill(['level' => 2, 'status' => ChildStatusEnum::Active])->save();

    return 2;
}

/** The church's last Moving-Up, which moved the world's child from Level 2 to 3 — so it can be put back. */
function movingUpOf(AccessWorld $w): MovingUp
{
    $w->child->forceFill(['level' => 3, 'status' => ChildStatusEnum::Active])->save();
    $movingUp = MovingUp::query()->create(['church_id' => $w->church->id, 'satellite_id' => $w->home->id]);
    $movingUp->children()->create([
        'church_id' => $w->church->id, 'child_id' => $w->child->id,
        'from_level' => 2, 'from_status' => ChildStatusEnum::Active, 'to_level' => 3, 'to_status' => ChildStatusEnum::Active,
    ]);

    return $movingUp;
}

/**
 * The second of Home's two Links (made once, so one world serves every guess) — second, so moving it up moves it, a
 * change that says nothing: its proof is quietChanges()'.
 */
function linkOf(AccessWorld $w): SatelliteLink
{
    $links = SatelliteLink::query()->where('satellite_id', $w->home->id)->orderBy('position')->get();
    if ($links->count() < 2) {
        SatelliteLink::factory()->for($w->home)->create(['label' => 'First', 'position' => 1]);
        SatelliteLink::factory()->for($w->home)->create(['label' => 'Second', 'position' => 2]);
    }

    return SatelliteLink::query()->where('satellite_id', $w->home->id)->where('label', 'Second')->firstOrFail();
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
        'serve.door.present' => fn (AccessWorld $w): bool => $w->checkIn->fresh()?->isPresent() === true,
        'admin.links.move' => fn (AccessWorld $w): bool => SatelliteLink::query()->where('satellite_id', $w->home->id)->orderBy('position')->value('label') === 'Second',
        'serve.door.head-count' => fn (AccessWorld $w): bool => SquadHeadCount::query()->where('session_id', $w->checkIn->session_id)->where('squad_id', $w->checkIn->squad_id)->value('head_count') === 3,
    ];
}

/**
 * What a response says, in the matrix's words. `$worked` is a quiet change's proof (quietChanges()): when given, it
 * alone decides whether a redirect worked.
 *
 * @param  TestResponse<Response>  $response
 */
function outcomeOf(TestResponse $response, string $method, ?bool $worked = null, bool $adminPage = false): string
{
    $status = $response->getStatusCode();
    $path = rtrim((string) parse_url((string) $response->headers->get('Location'), PHP_URL_PATH), '/');

    if ($status === 302 && $path === '/login') {
        return 'login';
    }
    if ($status === 404) {
        return '404';
    }
    if ($status === 302 && $adminPage && $path === '/home-sat/serve/door') {
        return 'serve';
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
    if (str_starts_with($route, 'serve.door.')) {
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
    $adminPage = $method === 'GET' && str_starts_with($route, 'admin.');
    expect(outcomeOf($response, $method, $proof === null ? null : $proof($world), $adminPage))->toBe($expected, "{$actor->value} on {$route}");
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
    return AccessMatrix::featureOf($route);
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
    it('opens the satellite home for any staff @story#42', cellTest())
        ->with(cells('satellite.home'));
    it('gives any staff the poster, and nobody else @story#80 @story#81', cellTest())
        ->with(cells('serve.poster'));
});

describe('the registration lists', function (): void {
    it('shows the families list to any staff, and nothing to anyone else @story#43 @story#44', cellTest())
        ->with(bothAreas('families.index'));
    it('downloads the families list for admins only @story#45', cellTest())
        ->with(cells('admin.families.export'));
    it('shows the children list to admins only, and nothing to anyone else @story#58 @story#59', cellTest())
        ->with(cells('admin.children.index'));
});

describe('the door: search, register, add a child', function (): void {
    it('lets any staff search the whole church @story#46', cellTest())
        ->with(bothAreas('families.register'));
    it('lets any staff register a family @story#47', cellTest())
        ->with(bothAreas('families.store'));
    it('lets any staff add a child to a family found, at any satellite @story#48 @story#49', cellTest())
        ->with(bothAreas('families.children.store'));
});

describe('a family, for staff', function (): void {
    it('opens for staff who can see its satellite @story#50 @story#51', cellTest())
        ->with(bothAreas('families.show'));
    it('is edited by staff who can open it — a Lifeshaper of its satellite too @story#52 @story#53', cellTest())
        ->with(bothAreas('families.update'));
    it('has its link resent by any staff member in the church @story#54 @story#55', cellTest())
        ->with(bothAreas('families.resend'));
    it('has its email corrected only by an admin who may edit it @story#56 @story#57', cellTest())
        ->with(cells('admin.families.email'));
});

describe('a child, for staff', function (): void {
    it('has its details fixed by staff who can open the family @story#60 @story#61', cellTest())
        ->with(bothAreas('children.update'));
    it('has its status set only by an admin who may edit the family @story#62 @story#63', cellTest())
        ->with(cells('admin.children.status'));
    it('has its Level corrected only by an admin of its satellite @story#64 @story#65', cellTest())
        ->with(cells('admin.children.level'));
    it('has in-person consent recorded by staff who can see the family @story#66 @story#67', cellTest())
        ->with(bothAreas('children.photo-consent'));
    it('has its photo taken by staff who can see the family @story#68 @story#69', cellTest())
        ->with(bothAreas('children.photo'));
    it('has a new name card asked for by staff who can see the family, never by a parent @story#130 @story#131', cellTest())
        ->with(bothAreas('card-requests.store'));
    it('has a printed card given at the desk by staff who can see the family @story#146 @story#147', cellTest())
        ->with(bothAreas('card-requests.give'));
});

describe('a photo deleted for good', function (): void {
    it('is deleted from Admin only by an admin who may edit the family @story#178 @story#179', cellTest())
        ->with(cells('admin.children.photo.destroy'));
    it('is deleted by the child\'s own parent, and nobody else @story#176 @story#177', cellTest())
        ->with(cells('family.children.photo.destroy'));
});

describe('Cards, for admins', function (): void {
    it('opens Cards for admins only @story#134 @story#135', cellTest())
        ->with(cells('admin.card-requests.index'));
    it('lets any admin make a print list of the approved cards they see @story#150 @story#151', cellTest())
        ->with(cells('admin.card-requests.print-lists.store'));
    it('downloads a print list holding a Home card only for an admin over Home @story#148 @story#149', cellTest())
        ->with(cells('admin.card-requests.print-lists.show'));
    it('marks a print list holding a Home card printed only for an admin over Home @story#144 @story#145', cellTest())
        ->with(cells('admin.card-requests.print-lists.mark-printed'));
    it('has a Home card marked paid only by an admin over Home @story#136 @story#137', cellTest())
        ->with(cells('admin.card-requests.mark-paid'));
    it('has a Home card approved only by an admin over Home @story#138 @story#139', cellTest())
        ->with(cells('admin.card-requests.approve'));
    it('has a Home card refused only by an admin over Home @story#140 @story#141', cellTest())
        ->with(cells('admin.card-requests.refuse'));
    it('has a Home card marked printed only by an admin over Home @story#142 @story#143', cellTest())
        ->with(cells('admin.card-requests.mark-printed'));
});

describe('duplicates and People, for admins', function (): void {
    it('lets a pair be answered only by an admin of the new family @story#72 @story#73', cellTest())
        ->with(cells('admin.duplicates.resolve'));
    it('opens People for admins only @story#74 @story#75', cellTest())
        ->with(cells('admin.people.index'));
    it('lets a home Lifeshaper be added only by an admin over that satellite @story#76 @story#77', cellTest())
        ->with(cells('admin.people.store'));
    it('lets a home Lifeshaper\'s role be removed only by an admin over that satellite @story#78 @story#79', cellTest())
        ->with(cells('admin.people.roles.destroy'));
});

describe('Links, for admins', function (): void {
    it('opens Links for admins only @story#164 @story#165', cellTest())
        ->with(cells('admin.links.index'));
    it('has a Link added to Home\'s poster only by an admin over Home @story#166 @story#167', cellTest())
        ->with(cells('admin.links.store'));
    it('has Home\'s Link changed only by an admin over Home @story#168 @story#169', cellTest())
        ->with(cells('admin.links.update'));
    it('has Home\'s Link moved only by an admin over Home @story#170 @story#171', cellTest())
        ->with(cells('admin.links.move'));
    it('has Home\'s Link removed only by an admin over Home @story#172 @story#173', cellTest())
        ->with(cells('admin.links.destroy'));
});

describe('Moving-Up, for an admin of the satellite', function (): void {
    it('opens Moving-Up for the satellite\'s admins only @story#154 @story#155', cellTest())
        ->with(cells('admin.moving-up.index'));
    it('moves children up only for the satellite\'s admins @story#156 @story#157', cellTest())
        ->with(cells('admin.moving-up.store'));
    it('puts one child back only for the satellite\'s admins @story#158 @story#159', cellTest())
        ->with(cells('admin.moving-up.children.put-back'));
    it('puts everyone back only for the satellite\'s admins @story#160 @story#161', cellTest())
        ->with(cells('admin.moving-up.put-back'));
});

describe('Classes, for admins', function (): void {
    it('opens Classes for admins only @story#95 @story#96', cellTest())
        ->with(cells('admin.schedules.index'));
    it('lets a class be added at Home only by an admin over Home @story#97 @story#98', cellTest())
        ->with(cells('admin.schedules.store'));
    it('lets a Home class be edited only by an admin over Home @story#99 @story#100', cellTest())
        ->with(cells('admin.schedules.update'));
    it('lets a Home class be retired only by an admin over Home @story#101 @story#102', cellTest())
        ->with(cells('admin.schedules.retire'));
});

describe('the door, for staff', function (): void {
    it('opens the door for any staff, with their own satellites\' classes @story#103 @story#104', cellTest())
        ->with(cells('serve.door.show'));
    it('lets children be checked in at Home\'s class only by staff whose Role covers Home @story#106 @story#109', cellTest())
        ->with(cells('serve.door.check-in'));
    it('lets a Check-in at Home be undone only by staff whose Role covers Home @story#110 @story#111', cellTest())
        ->with(cells('serve.door.undo'));
    it('opens Who\'s in for any staff, with their own satellites\' classes @story#117 @story#118', cellTest())
        ->with(cells('serve.door.whos-in'));
    it('lets a child at Home\'s class be marked present only by staff whose Role covers Home @story#119 @story#120', cellTest())
        ->with(cells('serve.door.present'));
    it('lets a head count at Home\'s class be confirmed only by staff whose Role covers Home @story#121 @story#123', cellTest())
        ->with(cells('serve.door.head-count'));
});

describe('Attendance, for admins', function (): void {
    it('shows Home\'s Session only to an admin over Home @story#112 @story#113', cellTest())
        ->with(cells('admin.attendance.show'));
    it('downloads Home\'s Session only for an admin over Home @story#114 @story#115', cellTest())
        ->with(cells('admin.attendance.export'));
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

/** @return list<string> the staff routes whose address names a family, child, pair, role, class or Check-in */
function routesNamingAnId(): array
{
    return array_values(array_filter(array_keys(matrix()), function (string $route): bool {
        $uri = (string) Router::getRoutes()->getByName($route)?->uri();

        return str_starts_with($uri, '{satellite}') && preg_match('/\{(family|child|pair|assignment|schedule|checkIn|cardRequest|printList|movingUp|link)\}/', $uri) === 1;
    }));
}

/**
 * Every Home id route, asked by `$actor` in THEIR satellite's address (`/{$slug}/…` for `/home-sat/…`): what each one
 * answers, by "METHOD address". Every refusal is a 404 and changes nothing, so one world serves every guess.
 *
 * @param  Closure(AccessWorld): array<string, mixed>  $payloadThere
 * @return array<string, string>
 */
function guessesFrom(Actor $actor, string $slug, Closure $payloadThere): array
{
    Notification::fake();
    Storage::fake('photos');
    travelTo(CarbonImmutable::parse(AccessWorld::SATURDAY, 'Asia/Dubai'));
    $world = AccessWorld::make();
    actingAs($world->user($actor) ?? throw new LogicException("{$actor->value} has a login."));

    $answers = [];
    foreach (routesNamingAnId() as $route) {
        [$method, $uri, $payload] = requestFor($route, $world);
        // A payload valid where they are, so only the id can refuse it.
        if (isset($payload['home_satellite_id'])) {
            $payload = $payloadThere($world);
        }
        $guessed = "/{$slug}".substr($uri, strlen('/home-sat'));
        $answers["{$method} {$guessed}"] = outcomeOf(call($method, $guessed, $payload), $method, null, $method === 'GET' && str_starts_with($route, 'admin.'));
    }

    return $answers;
}

it('refuses another church\'s admin who puts ccf\'s ids in their OWN satellite\'s address @story#29 @story#49 @story#51 @story#100 @story#102 @story#109 @story#111 @story#120 @story#123 @story#131 @story#137 @story#139 @story#141 @story#143 @story#145 @story#147 @story#149 @story#159 @story#161 @story#169 @story#171 @story#173 @story#177 @story#179', function (): void {
    $answers = guessesFrom(Actor::OtherChurch, 'other', fn (AccessWorld $w): array => familyEdit($w->otherChurch->satellites()->firstOrFail()));

    expect($answers)->toHaveCount(41)
        ->and(array_unique(array_values($answers)))->toBe(['404'], (string) json_encode($answers, JSON_PRETTY_PRINT));
});

it('refuses the other satellite\'s admin who puts Home\'s ids in Away\'s address — but a visiting family is helped from anywhere: its link resent, a child added @story#51 @story#54 @story#48', function (): void {
    $answers = guessesFrom(Actor::AwayAdmin, 'away', fn (AccessWorld $w): array => familyEdit($w->away));
    $helped = array_filter($answers, fn (string $key): bool => preg_match('#/families/\d+/(resend|children)$#', $key) === 1, ARRAY_FILTER_USE_KEY);

    expect(array_values($helped))->toBe(['ok', 'ok', 'ok', 'ok'])
        ->and(array_unique(array_values(array_diff_key($answers, $helped))))->toBe(['404'], (string) json_encode($answers, JSON_PRETTY_PRINT));
});

it('refuses an admin of both satellites who puts Home\'s ids in Away\'s address — they act on Home at Home\'s @story#51 @story#65 @story#157 @story#79', function (): void {
    $answers = guessesFrom(Actor::HomeAndAwayAdmin, 'away', fn (AccessWorld $w): array => familyEdit($w->away));
    $helped = array_filter($answers, fn (string $key): bool => preg_match('#/families/\d+/(resend|children)$#', $key) === 1, ARRAY_FILTER_USE_KEY);

    expect(array_values($helped))->toBe(['ok', 'ok', 'ok', 'ok'])
        ->and(array_unique(array_values(array_diff_key($answers, $helped))))->toBe(['404'], (string) json_encode($answers, JSON_PRETTY_PRINT));
});
