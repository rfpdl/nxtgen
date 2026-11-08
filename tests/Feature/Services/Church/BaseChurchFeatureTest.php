<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Domains\Church\DataObjects\AccessScope;
use App\Enums\RoleEnum;
use App\Foundation\Support\RequestContext;
use App\Services\Church\Features\BaseChurchFeature;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\childEdit;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * The staff base Feature starts from the satellite in the address, its church and the staff member's AccessScope, and
 * refuses (404, never 403) the child or family the address names unless the scope allows what the page does with that
 * family. The child or family itself is found within the address's church by its route binding
 * (RouteBindingServiceProvider, Roy 2026-11-08), so another church's id is a 404 before any Feature runs.
 */

/** @extends BaseChurchFeature<null> */
final class ScopedLookups extends BaseChurchFeature
{
    /** @param Closure(Family): bool $allows */
    public function child(Child $child, Closure $allows): Child
    {
        return $this->scopedChild($child, $allows);
    }

    /** @param Closure(Family): bool $allows */
    public function family(Family $family, Closure $allows): Family
    {
        return $this->scopedFamily($family, $allows);
    }

    /** @return array{0: Church, 1: Satellite, 2: AccessScope} */
    public function where(): array
    {
        return [$this->church(), $this->satellite(), $this->scope()];
    }

    public function handle(): null
    {
        return null;
    }
}

function atSatelliteRequest(Satellite $satellite): ScopedLookups
{
    RequestContext::putChurch(request(), $satellite->church);
    RequestContext::putSatellite(request(), $satellite);
    RequestContext::putScope(request(), new AccessScope($satellite->church_id, true, [], [], []));

    return new ScopedLookups;
}

function statusOf(Closure $lookup): ?int
{
    try {
        $lookup();
    } catch (HttpException $e) {
        return $e->getStatusCode();
    }

    return null;
}

it('hands back the child and the family when the scope allows it', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $child = childOf($family);
    $lookups = atSatelliteRequest($satellite);

    expect($lookups->child($child, fn (Family $f): bool => $f->is($family))->id)->toBe($child->id)
        ->and($lookups->family($family, fn (Family $f): bool => true)->id)->toBe($family->id)
        ->and($lookups->where()[1]->is($satellite))->toBeTrue();
});

it('refuses with a 404 when the scope does not allow it', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $child = childOf($family);
    $lookups = atSatelliteRequest($satellite);

    expect(statusOf(fn () => $lookups->child($child, fn (Family $f): bool => false)))->toBe(404)
        ->and(statusOf(fn () => $lookups->family($family, fn (Family $f): bool => false)))->toBe(404);
});

it('never finds a child or family of another church', function (): void {
    // The lookup moved from this base Feature to the route binding (Roy 2026-11-08): asked through the address.
    $other = familyAt(satelliteOf(church()));
    $child = childOf($other);
    $church = church();
    $own = familyAt(satelliteOf($church, ['slug' => 'dubai']));
    $admin = staffMember(RoleEnum::Admin, atChurch($church));

    actingAs($admin)->get("/dubai/admin/families/{$own->id}")->assertOk();
    actingAs($admin)->get("/dubai/admin/families/{$other->id}")->assertNotFound();
    actingAs($admin)->put("/dubai/admin/children/{$child->id}", childEdit())->assertNotFound();
});

it('is a 404 when no satellite, church or scope was resolved for the request', function (): void {
    expect(statusOf(fn () => (new ScopedLookups)->where()))->toBe(404);

    $satellite = satelliteOf(church());
    RequestContext::putChurch(request(), $satellite->church);
    expect(statusOf(fn () => (new ScopedLookups)->where()))->toBe(404);

    RequestContext::putSatellite(request(), $satellite);
    expect(statusOf(fn () => (new ScopedLookups)->where()))->toBe(404);
});
