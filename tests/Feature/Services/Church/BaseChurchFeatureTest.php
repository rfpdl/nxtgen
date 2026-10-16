<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Domains\Church\DataObjects\AccessScope;
use App\Foundation\Support\RequestContext;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * The staff base Feature loads the child or family a URL id names WITHIN the church in the address, and refuses
 * (404, never 403) unless the scope allows what the page does with that family.
 */

/** @extends BaseChurchFeature<null> */
final class ScopedLookups extends BaseChurchFeature
{
    /** @param Closure(Family): bool $allows */
    public function child(int $id, Closure $allows): Child
    {
        return $this->scopedChild($id, $allows);
    }

    /** @param Closure(Family): bool $allows */
    public function family(int $id, Closure $allows): Family
    {
        return $this->scopedFamily($id, $allows);
    }

    public function handle(): null
    {
        return null;
    }
}

function atChurchRequest(Church $church): ScopedLookups
{
    RequestContext::putChurch(request(), $church);
    RequestContext::putScope(request(), new AccessScope($church->id, true, false, [], [], []));

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
    $church = church();
    $family = familyAt(satelliteOf($church));
    $child = childOf($family);
    $lookups = atChurchRequest($church);

    expect($lookups->child($child->id, fn (Family $f): bool => $f->is($family))->id)->toBe($child->id)
        ->and($lookups->family($family->id, fn (Family $f): bool => true)->id)->toBe($family->id);
});

it('refuses with a 404 when the scope does not allow it', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $child = childOf($family);
    $lookups = atChurchRequest($church);

    expect(statusOf(fn () => $lookups->child($child->id, fn (Family $f): bool => false)))->toBe(404)
        ->and(statusOf(fn () => $lookups->family($family->id, fn (Family $f): bool => false)))->toBe(404);
});

it('never finds a child or family of another church', function (): void {
    $other = familyAt(satelliteOf(church()));
    $child = childOf($other);
    $lookups = atChurchRequest(church());

    expect(fn () => $lookups->child($child->id, fn (Family $f): bool => true))->toThrow(ModelNotFoundException::class)
        ->and(fn () => $lookups->family($other->id, fn (Family $f): bool => true))->toThrow(ModelNotFoundException::class);
});

it('is a 404 when no church was resolved for the request', function (): void {
    expect(statusOf(fn () => (new ScopedLookups)->child(1, fn (Family $f): bool => true)))->toBe(404);
});
