<?php

declare(strict_types=1);

use App\Data\Models\Church;
use Illuminate\Support\Facades\DB;

use function Tests\Support\atTheSameTime;
use function Tests\Support\church;
use function Tests\Support\withCommittedWorld;

/*
 * The real two-connection helper (TODO #7, #20): `withCommittedWorld` commits what the test made so other database
 * connections can see it (and wipes it after), and `atTheSameTime` runs each task in its own process, on its own
 * connection, released together. These prove the helper does what a concurrency test relies on.
 */

it('runs each task on its own database connection, at the same time', function (): void {
    withCommittedWorld(function (): void {
        $started = microtime(true);

        $results = atTheSameTime(
            fn (): array => [DB::scalar('SELECT CONNECTION_ID()'), DB::scalar('SELECT SLEEP(1)')],
            fn (): array => [DB::scalar('SELECT CONNECTION_ID()'), DB::scalar('SELECT SLEEP(1)')],
        );

        $connections = array_map(fn (mixed $r): int => is_array($r) && is_numeric($r[0]) ? (int) $r[0] : 0, $results);
        expect(array_unique($connections))->toHaveCount(2)
            ->and(microtime(true) - $started)->toBeLessThan(1.9);
    });
});

it('lets the other connections see what the test made, and wipes it afterwards', function (): void {
    $church = church(['slug' => 'seen']);

    withCommittedWorld(function () use ($church): void {
        [$seen] = atTheSameTime(fn (): mixed => Church::query()->whereKey($church->id)->value('slug'));

        expect($seen)->toBe('seen');
    });

    expect(Church::query()->count())->toBe(0);
});

it('hands back what a task threw, as an error in the test', function (): void {
    withCommittedWorld(function (): void {
        expect(fn () => atTheSameTime(fn () => throw new RuntimeException('boom')))
            ->toThrow(RuntimeException::class, 'boom');
    });
});
