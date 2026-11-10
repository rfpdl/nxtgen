<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Support\SatelliteCode;
use App\Services\Church\Http\Middleware\RequireAssignment;
use App\Services\Church\Http\Middleware\RequireSessionAssignment;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;

/*
 * The address rule (Roy 2026-11-07, spec items 5–6): the app is CCF's NxtGen only, so the church stays in the data and
 * never in the address — and every staff page names the satellite it is about, first: `/{satellite}/serve/…`,
 * `/{satellite}/admin/…`. A staff route without it would have to guess the satellite, which is how one satellite's
 * page shows another's families.
 *
 * ONE named exception (spec item 16): an opened class's door and Who's in are at the Session's own address,
 * `/{session}/checkin`, `/{session}/whos-in` — its CODE (8 letters and digits, never an id), behind
 * RequireSessionAssignment, which finds the satellite from the Session and asks for a Role there.
 */

/** @return list<Route> */
function appRoutes(): array
{
    return array_values(array_filter(Router::getRoutes()->getRoutes(), fn (Route $r): bool => str_starts_with((string) $r->getActionName(), 'App\\')));
}

it('finds the staff routes it is supposed to be guarding', function (): void {
    expect(count(array_filter(appRoutes(), fn (Route $r): bool => in_array(RequireAssignment::class, $r->gatherMiddleware(), true))))->toBeGreaterThan(30);
});

it('puts no church in any address', function (): void {
    $offenders = array_map(fn (Route $r): string => $r->uri(), array_filter(appRoutes(), fn (Route $r): bool => str_contains($r->uri(), '{church')));

    expect(array_values($offenders))->toBe([], 'The church is never in the address: '.implode(', ', $offenders));
});

it('starts every staff address with its satellite — but a Session\'s, which starts with its code', function (): void {
    $offenders = array_map(fn (Route $r): string => $r->uri(), array_filter(
        appRoutes(),
        fn (Route $r): bool => match (true) {
            in_array(RequireAssignment::class, $r->gatherMiddleware(), true) => ! str_starts_with($r->uri(), '{satellite}'),
            in_array(RequireSessionAssignment::class, $r->gatherMiddleware(), true) => ! str_starts_with($r->uri(), '{session}/'),
            default => str_starts_with($r->uri(), '{session}'),   // a Session address no middleware guards
        },
    ));

    expect(array_values($offenders))->toBe([], 'A staff address starts with {satellite}, a Session\'s with {session}: '.implode(', ', $offenders))
        ->and(count(array_filter(appRoutes(), fn (Route $r): bool => in_array(RequireSessionAssignment::class, $r->gatherMiddleware(), true))))->toBeGreaterThanOrEqual(2);
});

it('names a Session in an address by its code only, never by its id', function (): void {
    $sessionRoutes = array_filter(appRoutes(), fn (Route $r): bool => str_starts_with($r->uri(), '{session}/'));
    $ids = [];
    foreach ($sessionRoutes as $route) {
        $pattern = $route->wheres['session'] ?? null;
        if ($pattern !== '['.SatelliteCode::ALPHABET.']{'.SatelliteCode::LENGTH.'}') {
            $ids[] = "{$route->uri()} — {session} is not code-shaped";
        }
    }
    // A Session's id never appears in any address.
    foreach (appRoutes() as $route) {
        if (in_array('sessionId', $route->parameterNames(), true) || preg_match('/\{session[A-Z]/', $route->uri()) === 1) {
            $ids[] = $route->uri();
        }
    }

    expect($sessionRoutes)->not->toBeEmpty()->and($ids)->toBe([]);
});

/**
 * Names in a satellite address that are NOT looked up by a binder, with why. Every other name has its binder in
 * RouteBindingServiceProvider, which looks it up within the address satellite's church — Laravel's own implicit
 * binding would find the row in ANY church.
 */
const UNBOUND_ON_PURPOSE = [
    'squad' => 'An int the Feature looks up within the Session it is checking in to (GetSessionSquadJob).',
];

it('looks every id in a satellite or Session address up within that satellite\'s church', function (): void {
    $unbound = [];
    foreach (appRoutes() as $route) {
        if (! str_starts_with($route->uri(), '{satellite}') && ! str_starts_with($route->uri(), '{session}/')) {
            continue;
        }
        foreach (array_map(fn (mixed $n): string => is_string($n) ? $n : '', $route->parameterNames()) as $name) {
            if (Router::getBindingCallback($name) === null && ! array_key_exists($name, UNBOUND_ON_PURPOSE)) {
                $unbound[] = "{$route->uri()} — {{$name}}";
            }
        }
    }

    expect($unbound)->toBe([], 'Bind it in RouteBindingServiceProvider (within the church): '.implode(', ', $unbound));
});

it('keeps parents\' pages out of any satellite address, and registration in one', function (): void {
    $uris = array_map(fn (Route $r): string => (string) $r->getName().' '.$r->uri(), appRoutes());

    expect(array_values(array_filter($uris, fn (string $u): bool => str_starts_with($u, 'family.') && str_contains($u, '{satellite}'))))->toBe([])
        ->and(array_values(array_filter($uris, fn (string $u): bool => str_starts_with($u, 'public.register') && ! str_contains($u, ' {satellite}/register'))))->toBe([]);
});
