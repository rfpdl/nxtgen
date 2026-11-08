<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\MovingUp;
use App\Data\Models\PrintList;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\SatelliteLink;
use App\Data\Models\User;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use function Tests\Support\atSatellite;
use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/*
 * The route bindings (RouteBindingServiceProvider, Roy 2026-11-08): every id in a satellite's address is looked up
 * within THAT satellite's church — an id of another church is not found, whoever asks and whatever the page would
 * check after. Asked of each binder directly, so no later access check can hide a binder that looks too widely.
 */

/** The binder for `{$name}`. */
function binder(string $name): Closure
{
    return Router::getBindingCallback($name) ?? throw new LogicException("No binder for {{$name}}.");
}

/** What `{$name}` = `$id` binds to in an address at `$satellite`; null when it is not found. */
function bound(string $name, int $id, Satellite $satellite): ?Model
{
    $route = (new Route(['GET'], "{satellite}/x/{{$name}}", fn () => null))->bind(Request::create("/{$satellite->slug}/x/{$id}"));
    $here = binder('satellite')($satellite->slug, $route);
    $route->setParameter('satellite', $here instanceof Satellite ? $here : null);

    try {
        $model = binder($name)((string) $id, $route);
    } catch (HttpExceptionInterface|ModelNotFoundException) {
        return null;
    }

    return $model instanceof Model ? $model : null;
}

/**
 * One row of each bound kind, in a church of its own; keyed by the address name.
 *
 * @return array<string, int>
 */
function oneOfEach(Satellite $satellite): array
{
    $family = familyAt($satellite);
    guardianOf($family, User::factory()->create(), ['phone' => '0501234567']);
    $child = childOf($family, ['level' => 2]);
    $twin = familyAt($satellite);
    guardianOf($twin, User::factory()->create(), ['phone' => '0501234567']);
    (new CreateDuplicateCandidatesJob(family: $twin))->handle();
    $schedule = scheduleAt($satellite);
    $volunteer = User::factory()->create();
    grantRole($volunteer, RoleEnum::Lifeshaper, atSatellite($satellite));

    return [
        'family' => $family->id,
        'child' => $child->id,
        'schedule' => $schedule->id,
        'checkIn' => checkedIn(sessionOf($schedule), $child)->id,
        'cardRequest' => CardRequest::factory()->for($child)->create()->id,
        'printList' => PrintList::factory()->for($satellite->church)->create()->id,
        'movingUp' => MovingUp::query()->create(['church_id' => $satellite->church_id])->id,
        'link' => SatelliteLink::factory()->for($satellite)->create()->id,
        'assignment' => RoleAssignment::query()->where('user_id', $volunteer->id)->sole()->id,
        'pair' => DuplicateCandidate::query()->where('church_id', $satellite->church_id)->sole()->id,
    ];
}

it('finds each id of this church at its satellite\'s address, and none of another church\'s', function (): void {
    $dubai = satelliteOf(church(), ['slug' => 'dubai']);
    $ours = oneOfEach($dubai);
    $theirs = oneOfEach(satelliteOf(church(), ['slug' => 'elsewhere']));

    foreach ($ours as $name => $id) {
        expect(bound($name, $id, $dubai)?->getKey())->toBe($id, "{{$name}} of this church")
            ->and(bound($name, $theirs[$name], $dubai))->toBeNull("{{$name}} of another church");
    }
});

it('finds no satellite at an address nobody has', function (): void {
    satelliteOf(church(), ['slug' => 'dubai']);

    expect(fn () => binder('satellite')('sharjah', new Route(['GET'], '{satellite}', fn () => null)))
        ->toThrow(ModelNotFoundException::class);
});
