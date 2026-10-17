<?php

declare(strict_types=1);

namespace Tests\Feature\WireCasing;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\Finder\Finder;
use Tests\Support\AccessWorld;
use Tests\Support\Actor;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
 * Everything that crosses between the server and the browser is snake_case — page prop names included
 * (DECISIONS 2026-10-17, later — Roy's answers). This asks the app itself (only the list of pages it should reach is read from the Features' source): every GET
 * page the services serve, opened by every kind of person in AccessWorld, and every key at every depth of the props
 * each page receives — a Feature's own props, the shared props, the DTOs inside them, lazy closures resolved.
 *
 * The guard on the guard: every page a Feature renders must have been seen here, so a page this sample cannot reach
 * fails rather than going unchecked.
 */

/** A key that is camelCase: a lower-case letter or digit followed by a capital. */
const CAMEL = '/[a-z0-9][A-Z]/';

/**
 * The camelCase keys at any depth, as dotted paths (a list's items as `*`).
 *
 * @return list<string>
 */
function camelKeys(mixed $value, string $prefix = ''): array
{
    if (! is_array($value)) {
        return [];
    }

    $found = [];
    foreach ($value as $key => $item) {
        $path = ($prefix === '' ? '' : "{$prefix}.").(is_int($key) ? '*' : $key);
        if (is_string($key) && preg_match(CAMEL, $key) === 1) {
            $found[] = $path;
        }
        array_push($found, ...camelKeys($item, $path));
    }

    return array_values(array_unique($found));
}

/** @return list<Route> the GET routes the app's services answer */
function pageRoutes(): array
{
    return array_values(array_filter(
        Router::getRoutes()->getRoutes(),
        fn (Route $r): bool => in_array('GET', $r->methods(), true) && str_starts_with($r->getControllerClass() ?? '', 'App\\Services\\'),
    ));
}

/** A route's address in the world: its church, family and child are the world's; a link's token is any token. */
function addressOf(Route $route, AccessWorld $world): string
{
    $values = [
        'church' => $world->church->slug,
        'family' => (string) $world->family->id,
        'child' => (string) $world->child->id,
        'purpose' => 'login',
        'token' => 'any-token',
    ];

    return '/'.ltrim((string) preg_replace_callback(
        '/\{(\w+)\??\}/',
        fn (array $m): string => $values[$m[1]] ?? throw new \LogicException("No value for {{$m[1]}} in {$route->uri()}"),
        $route->uri(),
    ), '/');
}

/** @return list<string> every page component a Feature renders */
function renderedComponents(): array
{
    $pages = [];
    foreach (Finder::create()->files()->in(base_path('app/Services'))->name('*Feature.php') as $file) {
        preg_match_all("/Inertia::render\\(\\s*'([A-Za-z0-9\\/]+)'/", $file->getContents(), $matches);
        array_push($pages, ...$matches[1]);
    }
    $pages = array_values(array_unique($pages));
    sort($pages);

    return $pages;
}

it('sends no camelCase key, at any depth, on any page anyone can open', function (): void {
    Storage::fake('photos');
    $world = AccessWorld::make();
    $offenders = [];
    $seen = [];

    foreach (pageRoutes() as $route) {
        foreach (Actor::cases() as $actor) {
            Auth::forgetGuards();
            $user = $world->user($actor);
            if ($user !== null) {
                actingAs($user);
            }

            $view = get(addressOf($route, $world))->baseResponse->original ?? null;
            $page = $view instanceof View ? ($view->getData()['page'] ?? null) : null;
            if (! is_array($page) || ! is_string($page['component'] ?? null)) {
                continue;
            }

            $seen[$page['component']] = true;
            foreach (camelKeys($page['props'] ?? []) as $path) {
                $offenders["{$page['component']} → {$path}"] = true;
            }
        }
    }

    $seen = array_keys($seen);
    sort($seen);

    expect($seen)->toBe(renderedComponents(), 'A page this sample never reaches goes unchecked — open it here')
        ->and(array_keys($offenders))->toBe([], 'snake_case across the wire (DECISIONS 2026-10-17)');
});

it('finds a camelCase key however deep it sits', function (): void {
    // The guard on the guard's matcher.
    expect(camelKeys(['can_set_level' => true, 'grantOptions' => [['scope_id' => 1, 'scopeType' => 'x']], 'enums' => ['child_status' => []]]))
        ->toBe(['grantOptions', 'grantOptions.*.scopeType']);
});
