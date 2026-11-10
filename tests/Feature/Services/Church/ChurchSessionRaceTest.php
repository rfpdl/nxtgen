<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\GetSessionForUpdateJob;
use App\Domains\CheckIn\Jobs\UpdateSessionAsClosedJob;
use App\Enums\CheckInResultEnum;
use App\Enums\RoleEnum;
use App\Operations\CheckIn\CheckInChildOperation;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;
use function Tests\Support\atSatellite;
use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;
use function Tests\Support\withCommittedWorld;

/*
 * Closing a class while a check-in is under way (spec item 15; TODO #44, Codex): the Session's row lock decides the
 * order, so a child is never in after the close. Each test forces ONE order — the second task starts 0.15s after the
 * first, while the first holds the lock for 0.5s — instead of accepting whichever came first.
 */

/**
 * A Dubai church's class on Saturday 31 Oct 2026, a child to check in, a Lifeshaper and the satellite's admin.
 *
 * @return array{0: Session, 1: Child, 2: User, 3: User}
 */
function raceWorld(): array
{
    $satellite = satelliteOf(church(['timezone' => 'Asia/Dubai']));
    $session = sessionOf(scheduleAt($satellite), '2026-10-31');

    return [
        $session,
        childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']),
        staffMember(RoleEnum::Lifeshaper, atSatellite($satellite)),
        staffMember(RoleEnum::Admin, atSatellite($satellite)),
    ];
}

it('makes an admin\'s close wait for a check-in already under way: the child is in, and the class closed after @story#204', function (): void {
    [$session, $child, $staff, $admin] = raceWorld();
    travelTo(CarbonImmutable::parse('2026-10-31 13:30:00', 'Asia/Dubai'));

    withCommittedWorld(function () use ($session, $child, $staff, $admin): void {
        [$checkedInBy, $closedBy] = atTheSameTime(
            // The check-in, its transaction (and the Session's lock) held a moment longer, as a slow phone's would be.
            fn (): float => DB::transaction(function () use ($session, $child, $staff): float {
                expect((new CheckInChildOperation(session: $session, child: $child, level: null, makeActive: false, by: $staff))->handle())
                    ->toBe(CheckInResultEnum::CheckedIn);
                usleep(500_000);

                return microtime(true);
            }),
            // The admin taps Close — through CloseSessionFeature — once the check-in holds the lock.
            function () use ($session, $admin): float {
                usleep(150_000);
                actingAs($admin)->put("/{$session->code}/close")->assertRedirect();

                return microtime(true);
            },
        );

        expect(CheckIn::query()->where('session_id', $session->id)->count())->toBe(1)
            ->and($session->fresh()?->closed_at)->not->toBeNull()
            // The close could not land until the check-in was done: the check-in's open transaction holds the Session's
            // row (its lock, and the new Check-in's own hold on it), so the admin's UPDATE waits.
            ->and(is_float($closedBy) && is_float($checkedInBy) && $closedBy >= $checkedInBy)->toBeTrue();
    });
});

it('refuses a check-in that waited behind a close: it reads the Session once the lock is its own @story#204', function (): void {
    [$session, $child, $staff, $admin] = raceWorld();
    travelTo(CarbonImmutable::parse('2026-10-31 13:30:00', 'Asia/Dubai'));

    withCommittedWorld(function () use ($session, $child, $staff, $admin): void {
        [, $result] = atTheSameTime(
            // The close, not committed for a moment: a check-in that did not wait for the lock would still see it open.
            fn (): int => DB::transaction(function () use ($session, $admin): int {
                (new UpdateSessionAsClosedJob(session: (new GetSessionForUpdateJob(session: $session))->handle(), by: $admin))->handle();
                usleep(500_000);

                return $session->id;
            }),
            function () use ($session, $child, $staff): string {
                usleep(150_000);

                return (new CheckInChildOperation(session: $session, child: $child, level: null, makeActive: false, by: $staff))->handle()->value;
            },
        );

        expect($result)->toBe(CheckInResultEnum::Closed->value)
            ->and(CheckIn::query()->count())->toBe(0);
    });
});

it('refuses a check-in that waited across midnight: the day is read once the lock is its own, not before @story#204', function (): void {
    [$session, $child, $staff] = raceWorld();
    travelTo(CarbonImmutable::parse('2026-10-31 23:59:59', 'Asia/Dubai'));

    withCommittedWorld(function () use ($session, $child, $staff): void {
        $midnight = microtime(true) + 0.3 + 0.4;   // atTheSameTime starts both 0.3s from now; the lock is let go 0.5s in

        [, $result] = atTheSameTime(
            // Someone else's change at the door holds the Session's lock over midnight (a head count, an Undo…).
            fn (): int => DB::transaction(function () use ($session): int {
                (new GetSessionForUpdateJob(session: $session))->handle();
                usleep(500_000);

                return $session->id;
            }),
            function () use ($session, $child, $staff, $midnight): string {
                // This phone's clock passes midnight while it waits: Saturday before, Sunday after.
                Carbon::setTestNow(fn (): CarbonImmutable => CarbonImmutable::parse(
                    microtime(true) < $midnight ? '2026-10-31 23:59:59' : '2026-11-01 00:00:01',
                    'Asia/Dubai',
                ));
                usleep(150_000);

                return (new CheckInChildOperation(session: $session, child: $child, level: null, makeActive: false, by: $staff))->handle()->value;
            },
        );

        expect($result)->toBe(CheckInResultEnum::Closed->value)
            ->and(CheckIn::query()->count())->toBe(0);
    });
});
