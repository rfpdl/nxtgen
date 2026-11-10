<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\GetSessionForUpdateJob;
use App\Domains\CheckIn\Jobs\UpdateSessionAsClosedJob;
use App\Enums\CheckInResultEnum;
use App\Operations\CheckIn\CheckInChildOperation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\travelTo;
use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\withCommittedWorld;

/*
 * Two phones checking the same child in at the same moment, through the WHOLE Operation — its transaction included
 * (MySQL REPEATABLE READ: a plain read inside it does not see the other phone's new row). Both phones must hear
 * "checked in" or "already checked in", never an error, and there is one Check-in, one Session, one Squad. And a
 * child is checked in only while the Session is open (spec item 15) — read under the Session's lock, so an admin
 * closing it at that moment is either before or after, never half-way.
 */

/** @return list<string> both phones' results, sorted */
function bothPhones(Session $session, Child $child): array
{
    travelTo(CarbonImmutable::parse('2026-10-31 10:00:00'));   // Saturday, wherever the church is
    [$a, $b] = [User::factory()->create(), User::factory()->create()];
    $results = [];

    withCommittedWorld(function () use ($session, $child, $a, $b, &$results): void {
        $results = atTheSameTime(
            fn (): string => (new CheckInChildOperation(session: $session, child: $child, level: null, makeActive: false, by: $a))->handle()->value,
            fn (): string => (new CheckInChildOperation(session: $session, child: $child, level: null, makeActive: false, by: $b))->handle()->value,
        );
        expect([CheckIn::query()->count(), Session::query()->count(), Squad::query()->where('satellite_id', $session->satellite_id)->where('level', 2)->where('gender', 'girl')->count()])
            ->toBe([1, 1, 1]);
    });

    $values = array_map(fn (mixed $r): string => is_string($r) ? $r : 'not a result', $results);
    sort($values);

    return $values;
}

it('answers both phones when the Session is already there', function (): void {
    $satellite = Satellite::factory()->for(church())->withSquads()->create();
    $session = sessionOf(scheduleAt($satellite), '2026-10-31');
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']);

    expect(bothPhones($session, $child))->toBe([CheckInResultEnum::AlreadyIn->value, CheckInResultEnum::CheckedIn->value]);
});

it('answers both phones at a satellite missing the Squad', function (): void {
    $satellite = satelliteOf(church());
    $session = sessionOf(scheduleAt($satellite), '2026-10-31');
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']);

    expect(bothPhones($session, $child))->toBe([CheckInResultEnum::AlreadyIn->value, CheckInResultEnum::CheckedIn->value]);
});

it('checks nobody in to a closed Session, or one whose day has gone — the church\'s date read once the Session is locked (Astra)', function (string $now, bool $closed): void {
    $satellite = satelliteOf(church(['timezone' => 'Asia/Dubai']));
    $session = sessionOf(scheduleAt($satellite), '2026-10-31');
    if ($closed) {
        $session->update(['closed_at' => now()]);
    }
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']);
    travelTo(CarbonImmutable::parse($now, 'Asia/Dubai'));

    $result = (new CheckInChildOperation(session: $session, child: $child, level: null, makeActive: false, by: User::factory()->create()))->handle();

    expect($result)->toBe(CheckInResultEnum::Closed)
        ->and(CheckIn::query()->count())->toBe(0);
})->with([
    'closed by an admin' => ['2026-10-31 14:00', true],
    'just after midnight at the satellite' => ['2026-11-01 00:00:05', false],
]);

it('reads the Session as it is now — closed since the page loaded it, it checks nobody in', function (): void {
    $satellite = satelliteOf(church());
    $session = sessionOf(scheduleAt($satellite), '2026-10-31');
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']);
    Session::query()->whereKey($session->id)->update(['closed_at' => now()]);   // another admin, meanwhile
    travelTo(CarbonImmutable::parse('2026-10-31 10:00:00'));

    $result = (new CheckInChildOperation(session: $session, child: $child, level: null, makeActive: false, by: User::factory()->create()))->handle();

    expect($result)->toBe(CheckInResultEnum::Closed)
        ->and(CheckIn::query()->count())->toBe(0);
});

it('never checks a child in after an admin closed the Session at the same moment — the close comes before or after', function (): void {
    $satellite = satelliteOf(church());
    $session = sessionOf(scheduleAt($satellite), '2026-10-31');
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']);
    [$staff, $admin] = [User::factory()->create(), User::factory()->create()];
    travelTo(CarbonImmutable::parse('2026-10-31 10:00:00'));

    withCommittedWorld(function () use ($session, $child, $staff, $admin): void {
        [$result] = atTheSameTime(
            fn (): string => (new CheckInChildOperation(session: $session, child: $child, level: null, makeActive: false, by: $staff))->handle()->value,
            fn (): int => DB::transaction(fn (): int => (new UpdateSessionAsClosedJob(session: (new GetSessionForUpdateJob(session: $session))->handle(), by: $admin))->handle()->id),
        );

        // Either the child got in while it was open, or the close came first and nobody did — never in after a close.
        expect($session->fresh()?->closed_at)->not->toBeNull()
            ->and($result === CheckInResultEnum::CheckedIn->value ? CheckIn::query()->count() : 1 - CheckIn::query()->count())->toBe(1)
            ->and($result)->toBeIn([CheckInResultEnum::CheckedIn->value, CheckInResultEnum::Closed->value]);
    });
});
