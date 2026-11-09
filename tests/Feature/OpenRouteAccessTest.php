<?php

declare(strict_types=1);

namespace Tests\Feature\OpenRouteAccess;

use App\Data\Models\FamilyClaim;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Enums\MagicLinkPurposeEnum;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\Support\AccessWorld;
use Tests\Support\Actor;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;

/*
 * The access matrix's sibling (TODO #18, SECURITY #4): the routes of the Auth and Public services — sign-in, emailed
 * links, the landing page, the backup password, public registration. They have no access row (AccessRowTest guards
 * Church and Family only): most are open to anyone, a few need a signed-in user, and the password pages need staff.
 *
 * Four actors are the ones that differ here: anonymous, a parent, a church admin, and another church's admin. Each
 * cell is one request in a fresh AccessWorld; the answer is the status and where it redirects, and no cell may end in
 * validation errors or an error message. Emailed links are real ones, made for the parent.
 */

/** @return list<Actor> */
function openActors(): array
{
    return [Actor::Anonymous, Actor::Parent, Actor::HomeAndAwayAdmin, Actor::OtherChurch];
}

/**
 * Route name => the answer for each of openActors(), in order: a status, and for a redirect its path.
 *
 * @return array<string, list<string>>
 */
function openMatrix(): array
{
    $all = fn (string $answer): array => array_fill(0, 4, $answer);

    return [
        'public.welcome' => $all('200'),
        'login' => $all('200'),
        // Signed in already: `guest` sends them home, the same place a correct password would.
        'auth.password.login' => $all('302 /home'),
        'auth.link.request' => $all('302 /login'),
        'logout' => ['302 /login', '302 /', '302 /', '302 /'],
        // /loginp: the password page; signed in already, home. /loginc: everyone ends signed out at sign in.
        'login.password' => ['200', '302 /home', '302 /home', '302 /home'],
        'login.clear' => $all('302 /login'),
        // Holding an emailed link is the permission: it works for whoever brings it.
        'auth.magic.confirm' => $all('200'),
        'auth.magic.login' => $all('302 /home'),
        'auth.magic.verify' => $all('302 /home'),
        'auth.magic.claim' => $all('302 /home'),
        // One place: go there — a parent to /family, staff to their first satellite's home (/{satellite}, the church
        // is never in the address — Roy 2026-11-07). The other church's admin is no parent any more (AccessWorld), so
        // they go straight to their own satellite rather than being asked to choose.
        // An admin at two satellites who used neither yet picks one (spec item 11); one satellite goes straight there.
        'home' => ['302 /login', '302 /family', '302 /pick', '302 /other'],
        // Only a place on the person's own landing list: an admin with no family has no "/family".
        'home.choice' => ['302 /login', '302 /family', '404', '404'],
        // "Pick your satellite" (spec item 11): for staff — the admin at Home and Away picks; a parent has none to pick.
        'pick' => ['302 /login', '404', '200', '200'],
        // The backup password is for staff (anywhere), never a parent.
        'account.password' => ['302 /login', '404', '200', '200'],
        'account.password.update' => ['302 /login', '404', '302 /', '302 /'],
        'public.register' => $all('200'),
        'public.register.store' => $all('302 /home-sat/register/thanks'),
        'public.register.thanks' => $all('200'),
    ];
}

/**
 * The request a route's cell makes.
 *
 * @return array{0: string, 1: string, 2: array<string, mixed>}
 */
function openRequest(string $route, AccessWorld $w): array
{
    $parent = $w->user(Actor::Parent) ?? throw new LogicException('The world has a parent.');
    $link = fn (MagicLinkPurposeEnum $purpose, ?FamilyClaim $claim = null): string => (new CreateMagicLinkJob(
        purpose: $purpose, email: $parent->email, user: $claim === null ? $parent : null, claim: $claim,
    ))->handle()->token;
    $password = ['password' => 'a-long-passphrase', 'password_confirmation' => 'a-long-passphrase'];

    return match ($route) {
        'public.welcome' => ['GET', '/', []],
        'login' => ['GET', '/login', []],
        'auth.password.login' => ['POST', '/login/password', ['email' => (string) $w->user(Actor::HomeAndAwayAdmin)?->email, 'password' => 'a-long-passphrase']],
        'auth.link.request' => ['POST', '/login/link', ['email' => 'someone@example.com']],
        'logout' => ['POST', '/logout', []],
        'login.password' => ['GET', '/loginp', []],
        'login.clear' => ['GET', '/loginc', []],
        'auth.magic.confirm' => ['GET', '/magic/login/'.$link(MagicLinkPurposeEnum::Login), []],
        'auth.magic.login' => ['POST', '/magic/login/'.$link(MagicLinkPurposeEnum::Login), []],
        'auth.magic.verify' => ['POST', '/magic/verify/'.$link(MagicLinkPurposeEnum::VerifyEmail), []],
        'auth.magic.claim' => ['POST', '/magic/claim/'.$link(MagicLinkPurposeEnum::ClaimFamily, FamilyClaim::factory()->create(['email_normalised' => $parent->email])), []],
        'home' => ['GET', '/home', []],
        'home.choice' => ['POST', '/home/choice', ['place' => 'family']],
        'pick' => ['GET', '/pick', []],
        'account.password' => ['GET', '/account/password', []],
        'account.password.update' => ['PUT', '/account/password', $password],
        // A satellite's registration link (spec item 7): the satellite in the address, never the church.
        'public.register' => ['GET', '/home-sat/register', []],
        'public.register.store' => ['POST', '/home-sat/register', [
            'home_satellite_id' => $w->home->id, 'area_text' => 'Al Barsha', 'privacy_consented' => true,
            'guardian' => ['first_name' => 'Zara', 'last_name' => 'Cruz', 'relationship' => 'mother', 'phone' => '0544897346', 'email' => 'zara@example.com', 'ministries' => []],
            'children' => [['first_name' => 'Ana', 'middle_name' => null, 'last_name' => 'Cruz', 'nickname' => 'Ana', 'gender' => 'girl', 'date_of_birth' => '2019-05-10', 'has_allergies' => false, 'allergies' => null]],
        ]],
        'public.register.thanks' => ['GET', '/home-sat/register/thanks', []],
        default => throw new LogicException("No request for {$route} — add it to openRequest()."),
    };
}

/** @return array<string, array{0: string, 1: Actor, 2: string}> */
function openCells(): array
{
    $cells = [];
    foreach (openMatrix() as $route => $answers) {
        foreach (openActors() as $i => $actor) {
            $cells["{$route} · {$actor->value} → {$answers[$i]}"] = [$route, $actor, $answers[$i]];
        }
    }

    return $cells;
}

/** @return list<string> the routes the Auth and Public services serve */
function openRouteNames(): array
{
    $names = array_values(array_map(
        fn (Route $r): string => (string) $r->getName(),
        array_filter(Router::getRoutes()->getRoutes(), fn (Route $r): bool => preg_match('/^App\\\\Services\\\\(Auth|Public)\\\\/', $r->getControllerClass() ?? '') === 1),
    ));
    sort($names);

    return $names;
}

it('has a row for every Auth and Public route, and no row for a route that is gone', function (): void {
    $rows = array_keys(openMatrix());
    sort($rows);

    expect(openRouteNames())->toHaveCount(19)->toBe($rows);
});

it('opens public pages to everyone, signed-in pages to the signed in, the password page to staff @story#1 @story#4 @story#12 @story#19 @story#20 @story#24 @story#162 @story#163', function (string $route, Actor $actor, string $expected): void {
    Notification::fake();
    Storage::fake('photos');
    $world = AccessWorld::make();
    $world->user(Actor::HomeAndAwayAdmin)?->forceFill(['password' => 'a-long-passphrase'])->save();
    [$method, $uri, $payload] = openRequest($route, $world);
    $user = $world->user($actor);
    if ($user !== null) {
        actingAs($user);
    }

    $response = call($method, $uri, $payload);
    $path = rtrim((string) parse_url((string) $response->headers->get('Location'), PHP_URL_PATH), '/');
    $answer = $response->getStatusCode() === 302 ? '302 '.($path === '' ? '/' : $path) : (string) $response->getStatusCode();

    expect($answer)->toBe($expected)
        ->and(session()->has('errors') || session()->has('error'))->toBeFalse("{$route}: validation errors or an error message");
})->with(openCells());
