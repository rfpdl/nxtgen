<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Services\Church\Http\Middleware\RequireAssignment;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;

/*
 * The address rule (Roy 2026-11-07, spec items 5–6): the app is CCF's NxtGen only, so the church stays in the data and
 * never in the address — and every staff page names the satellite it is about, first: `/{satellite}/serve/…`,
 * `/{satellite}/admin/…`. A staff route without it would have to guess the satellite, which is how one satellite's
 * page shows another's families.
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

it('starts every staff address with its satellite', function (): void {
    $offenders = array_map(fn (Route $r): string => $r->uri(), array_filter(
        appRoutes(),
        fn (Route $r): bool => in_array(RequireAssignment::class, $r->gatherMiddleware(), true) && ! str_starts_with($r->uri(), '{satellite}'),
    ));

    expect(array_values($offenders))->toBe([], 'A staff address starts with {satellite}: '.implode(', ', $offenders));
});

/**
 * Names in a satellite address that are NOT looked up by a binder, with why. Every other name has its binder in
 * RouteBindingServiceProvider, which looks it up within the address satellite's church — Laravel's own implicit
 * binding would find the row in ANY church.
 */
const UNBOUND_ON_PURPOSE = [
    'squad' => 'An int the Feature looks up within the Session it is checking in to (GetSessionSquadJob).',
];

it('looks every id in a satellite address up within that satellite\'s church', function (): void {
    $unbound = [];
    foreach (appRoutes() as $route) {
        if (! str_starts_with($route->uri(), '{satellite}')) {
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
