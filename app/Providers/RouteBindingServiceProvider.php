<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Domains\Card\Jobs\GetCardRequestJob;
use App\Domains\Card\Jobs\GetPrintListJob;
use App\Domains\CheckIn\Jobs\GetCheckInJob;
use App\Domains\CheckIn\Jobs\GetSessionByCodeJob;
use App\Domains\Church\Jobs\GetRoleAssignmentJob;
use App\Domains\Church\Jobs\GetSatelliteByCodeJob;
use App\Domains\Church\Jobs\GetSatelliteBySlugJob;
use App\Domains\Church\Jobs\GetSatelliteLinkJob;
use App\Domains\Family\Jobs\GetChildWithFamilyJob;
use App\Domains\Family\Jobs\GetChurchChildJob;
use App\Domains\Family\Jobs\GetDuplicateCandidateJob;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Domains\Family\Jobs\GetFamilyChildJob;
use App\Domains\Family\Jobs\GetOwnGuardianJob;
use App\Domains\Level\Jobs\GetMovingUpJob;
use App\Domains\Schedule\Jobs\GetScheduleJob;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lucid\Bus\UnitDispatcher;

/**
 * What a name in an address is (Roy 2026-11-08): controllers take the model, never a raw id. `{satellite}` is the
 * satellite the address names, with its church. Every other id is looked up within THAT church (the address names no
 * church — spec item 5), so an id from anywhere else is a 404 before any page runs; WHO may use it is still the
 * Feature's question (AccessScope). Each lookup is the Job a Feature used before, so the rows and relations loaded are
 * the same.
 *
 * Binders run in the address's order, so `{satellite}` is a model by the time an id is looked up. `{child}` without a
 * satellite is a parent's own (`/family/children/{child}`: within their own family only) or a photo link (signed; the
 * Feature checks who is looking).
 */
final class RouteBindingServiceProvider extends ServiceProvider
{
    use UnitDispatcher;

    public function boot(): void
    {
        Route::bind('satellite', function (string $address, RoutingRoute $route): Satellite {
            try {
                return $this->run(new GetSatelliteBySlugJob(slug: $address));
            } catch (ModelNotFoundException $notAnAddress) {
                // The registration page alone also takes a satellite's code — its printed QR poster (spec item 7) — and
                // forwards it to the satellite's address (ShowRegisterFeature).
                $byCode = $route->getName() === 'public.register' ? $this->run(new GetSatelliteByCodeJob(code: $address)) : null;

                return $byCode ?? throw $notAnAddress;
            }
        });

        // A Session's address carries its code (spec item 16); RequireSessionAssignment then asks for a Role at its
        // satellite.
        Route::bind('session', fn (string $code): Session => $this->run(new GetSessionByCodeJob(code: $code)) ?? abort(404));

        $this->inChurch('family', fn (Church $church, int $id) => $this->run(new GetFamilyByIdJob(church: $church, id: $id)));
        $this->inChurch('schedule', fn (Church $church, int $id) => $this->run(new GetScheduleJob(church: $church, id: $id)));
        $this->inChurch('checkIn', fn (Church $church, int $id) => $this->run(new GetCheckInJob(church: $church, id: $id)));
        $this->inChurch('cardRequest', fn (Church $church, int $id) => $this->run(new GetCardRequestJob(church: $church, id: $id)));
        $this->inChurch('printList', fn (Church $church, int $id) => $this->run(new GetPrintListJob(church: $church, id: $id)));
        $this->inChurch('movingUp', fn (Church $church, int $id) => $this->run(new GetMovingUpJob(church: $church, id: $id)));
        $this->inChurch('link', fn (Church $church, int $id) => $this->run(new GetSatelliteLinkJob(church: $church, id: $id)));
        $this->inChurch('assignment', fn (Church $church, int $id) => $this->run(new GetRoleAssignmentJob(church: $church, id: $id)));
        $this->inChurch('pair', fn (Church $church, int $id) => $this->run(new GetDuplicateCandidateJob(church: $church, id: $id)));

        Route::bind('child', function (string $id, RoutingRoute $route) {
            $satellite = $route->parameter('satellite');
            if ($satellite instanceof Satellite) {
                return $this->run(new GetChurchChildJob(church: $satellite->church, id: (int) $id));
            }
            // A parent's own page: only a child of their OWN family, before any form is read — another family's child
            // answers as no child at all (Astra 2026-11-08).
            if (Str::startsWith((string) $route->getName(), 'family.')) {
                $user = request()->user();
                $guardian = $user instanceof User ? $this->run(new GetOwnGuardianJob(user: $user)) : null;
                abort_if($guardian === null, 404);

                return $this->run(new GetFamilyChildJob(family: $guardian->family, id: (int) $id));
            }

            // A photo link: signed (checked before this — bootstrap/app.php); the Feature checks the viewer.
            return $this->run(new GetChildWithFamilyJob(id: (int) $id)) ?? abort(404);
        });
    }

    /** `{$name}` as the model `$find` gives, within the church of the address's satellite — or its Session's. */
    private function inChurch(string $name, Closure $find): void
    {
        Route::bind($name, function (string $id, RoutingRoute $route) use ($find) {
            $satellite = $route->parameter('satellite');
            $session = $route->parameter('session');
            $church = match (true) {
                $satellite instanceof Satellite => $satellite->church,
                $session instanceof Session => $session->satellite->church,
                default => abort(404),
            };

            return $find($church, (int) $id);
        });
    }
}
