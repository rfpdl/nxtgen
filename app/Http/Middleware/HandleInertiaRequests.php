<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\Models\User;
use App\Domains\Church\DataObjects\ChurchData;
use App\Domains\Church\DataObjects\ChurchNavData;
use App\Domains\Church\DataObjects\SatelliteChoiceData;
use App\Domains\Church\DataObjects\SatelliteData;
use App\Domains\Church\Jobs\GetLandingOptionsJob;
use App\Domains\Church\Jobs\GetOpenSatellitesJob;
use App\Foundation\Support\RequestContext;
use App\Foundation\Support\RouteArea;
use App\Foundation\Support\RouteSatellite;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Lucid\Bus\UnitDispatcher;

/**
 * Props every page receives. Like every prop, their names and fields are snake_case (DECISIONS 2026-10-17,
 * later — Roy's answers); the frontend's `SharedProps` (resources/js/types) mirrors this method.
 *
 * Every flash key here must also be READ by a screen — the FlashContractTest enforces both legs, because a
 * key flashed and shared but read by nothing is a message nobody ever sees (PoC lesson).
 */
final class HandleInertiaRequests extends Middleware
{
    use UnitDispatcher;

    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user instanceof User ? [
                    'id' => $user->id,
                    'name' => $user->name,
                ] : null,
                // More than one place to go (a parent who serves, or two churches) → the "Switch" link.
                'can_switch' => fn (): bool => $user instanceof User && count($this->run(new GetLandingOptionsJob(user: $user))) > 1,
                // The platform admin (spec item 12) → the bar's "Platform" link (Roy 2026-11-10).
                'is_platform_admin' => $user instanceof User && $user->is_platform_admin,
            ],

            // The church a page is about: the address's satellite's (staff pages, registration), or the parent's own
            // (family pages). Read after the route's middleware has resolved and checked it; null elsewhere.
            'church' => fn (): ?ChurchData => ($church = RequestContext::church($request) ?? RouteSatellite::from($request)?->church) !== null ? ChurchData::fromModel($church) : null,

            // The bar's satellite switch (spec item 13): on a staff page, the satellites its viewer may open; else none.
            'open_satellites' => fn (): array => $user instanceof User && RequestContext::scope($request) !== null
                ? array_map(SatelliteChoiceData::fromModel(...), $this->run(new GetOpenSatellitesJob(user: $user)))
                : [],

            // The satellite the address names (/{satellite}/…, Roy 2026-11-07): staff pages build their links from it.
            'satellite' => fn (): ?SatelliteData => ($satellite = RouteSatellite::from($request)) !== null ? SatelliteData::fromModel($satellite) : null,

            // The staff menu (ChurchShell), from the AccessScope RequireAssignment put on THIS request; null on any
            // page that is not a staff page. Navigation only — every Feature still asks the scope itself.
            'church_nav' => fn (): ?ChurchNavData => ($scope = RequestContext::scope($request)) !== null ? ChurchNavData::fromScope($scope, RouteArea::of($request)) : null,

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
