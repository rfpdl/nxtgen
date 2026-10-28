<?php

declare(strict_types=1);

use App\Data\Models\Satellite;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\SquadHeadCount;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\UpdateSquadHeadCountJob;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\atTheSameTime;
use function Tests\Support\church;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\withCommittedWorld;

/*
 * A Squad's head count at a Session (GLOSSARY "Roll call"): one per Squad per Session, latest wins with who and when,
 * and two leaders confirming at the same moment still make one.
 */

/** @return array{session: Session, squad: Squad} */
function countedSquad(): array
{
    $dxb = Satellite::factory()->for(church())->withSquads()->create();

    return [
        'session' => sessionOf(scheduleAt($dxb)),
        'squad' => Squad::query()->where('satellite_id', $dxb->id)->where('level', 2)->where('gender', 'girl')->sole(),
    ];
}

it('confirms a Squad\'s head count at the Session, saying who and when', function (): void {
    ['session' => $session, 'squad' => $squad] = countedSquad();
    $joy = User::factory()->create();
    travelTo(CarbonImmutable::parse('2026-10-31 09:20:00'));

    (new UpdateSquadHeadCountJob(session: $session, squad: $squad, count: 4, by: $joy))->handle();

    $row = SquadHeadCount::query()->sole();
    expect([$row->church_id, $row->session_id, $row->squad_id, $row->head_count, $row->counted_by, $row->counted_at->toDateTimeString()])
        ->toBe([$session->church_id, $session->id, $squad->id, 4, $joy->id, '2026-10-31 09:20:00']);
});

it('replaces the count when confirmed again — the latest, by whoever confirmed it', function (): void {
    ['session' => $session, 'squad' => $squad] = countedSquad();
    $joy = User::factory()->create();
    $ben = User::factory()->create();

    (new UpdateSquadHeadCountJob(session: $session, squad: $squad, count: 4, by: $joy))->handle();
    travelTo(CarbonImmutable::parse('2026-10-31 10:40:00'));
    (new UpdateSquadHeadCountJob(session: $session, squad: $squad, count: 3, by: $ben))->handle();

    $row = SquadHeadCount::query()->sole();
    expect([$row->head_count, $row->counted_by, $row->counted_at->toDateTimeString()])->toBe([3, $ben->id, '2026-10-31 10:40:00']);
});

it('keeps one count per Squad: another Squad, or another Session, has its own', function (): void {
    ['session' => $session, 'squad' => $squad] = countedSquad();
    $boys = Squad::query()->where('satellite_id', $squad->satellite_id)->where('level', 2)->where('gender', 'boy')->sole();
    $nextWeek = sessionOf($session->schedule, '2026-11-07');
    $joy = User::factory()->create();

    (new UpdateSquadHeadCountJob(session: $session, squad: $squad, count: 4, by: $joy))->handle();
    (new UpdateSquadHeadCountJob(session: $session, squad: $boys, count: 2, by: $joy))->handle();
    (new UpdateSquadHeadCountJob(session: $nextWeek, squad: $squad, count: 5, by: $joy))->handle();

    expect(SquadHeadCount::query()->orderBy('id')->pluck('head_count')->all())->toBe([4, 2, 5]);
});

it('makes ONE head count when two leaders confirm the same Squad at the same moment — the first count, or a later one', function (bool $counted): void {
    ['session' => $session, 'squad' => $squad] = countedSquad();
    if ($counted) {
        SquadHeadCount::factory()->create(['session_id' => $session->id, 'squad_id' => $squad->id, 'head_count' => 9]);
    }
    $joy = User::factory()->create();
    $ben = User::factory()->create();

    withCommittedWorld(function () use ($session, $squad, $joy, $ben): void {
        $results = atTheSameTime(
            fn (): int => (new UpdateSquadHeadCountJob(session: $session, squad: $squad, count: 4, by: $joy))->handle()->head_count,
            fn (): int => (new UpdateSquadHeadCountJob(session: $session, squad: $squad, count: 5, by: $ben))->handle()->head_count,
        );

        // Each is told the count as it stands after its write — its own, or the other's if that landed after it.
        expect(array_filter($results, fn (mixed $r): bool => ! in_array($r, [4, 5], true)))->toBe([])
            ->and(SquadHeadCount::query()->count())->toBe(1)
            ->and(SquadHeadCount::query()->sole()->head_count)->toBeIn([4, 5]);
    });
})->with(['no count yet' => [false], 'already counted' => [true]]);
