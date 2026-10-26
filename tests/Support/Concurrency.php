<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/*
 * Real concurrency tests (TODO #7, #20): two phones pressing at once, not a simulation. Each task runs in its own
 * forked process with its own MySQL connection, and all are released at the same moment, so a check-then-insert race
 * really races and a unique key really decides.
 *
 * Two parts, because RefreshDatabase keeps every test inside one uncommitted transaction that no other connection
 * can see:
 *
 *   withCommittedWorld(function () {      // commits what the test has made so far; wipes every table afterwards
 *       $results = atTheSameTime(
 *           fn () => (new CreateCheckInJob(…))->handle(),
 *           fn () => (new CreateCheckInJob(…))->handle(),
 *       );
 *   });
 *
 * A task returns something serialisable (a scalar, an enum, an array of them) — a model would come back detached.
 * Whatever a task throws is thrown again in the test.
 */

/**
 * Commit what the test has made, run `$test`, then empty every table and reopen RefreshDatabase's transaction, so the
 * next test starts as clean as always.
 *
 * @param  Closure(): void  $test
 */
function withCommittedWorld(Closure $test): void
{
    $db = DB::connection();
    while ($db->transactionLevel() > 0) {
        $db->commit();
    }

    try {
        $test();
    } finally {
        $db->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($db->getSchemaBuilder()->getTableListing(schemaQualified: false) as $table) {
            // DELETE, not TRUNCATE: the tables are tiny, and TRUNCATE is DDL — seconds per test, for twenty tables.
            if ($table !== 'migrations') {
                $db->table($table)->delete();
            }
        }
        $db->statement('SET FOREIGN_KEY_CHECKS=1');
        $db->beginTransaction();
    }
}

/**
 * Run the tasks at the same moment, each in its own process on its own connection, and return their results in
 * order. Call it inside withCommittedWorld().
 *
 * @param  Closure(): mixed  ...$tasks
 * @return list<mixed>
 */
function atTheSameTime(Closure ...$tasks): array
{
    // The forked children inherit this process's socket: closing it in a child would close it here too. So it is
    // closed BEFORE forking, and every process (this one included, later) opens its own.
    DB::disconnect();
    $go = microtime(true) + 0.3;
    $files = [];
    $pids = [];

    foreach ($tasks as $i => $task) {
        $files[$i] = (string) tempnam(sys_get_temp_dir(), 'nxtgen-concurrent-');
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork a process for a concurrent task.');
        }
        if ($pid === 0) {
            runChildTask($task, $files[$i], $go);
        }
        $pids[$i] = $pid;
    }

    $results = [];
    foreach ($pids as $i => $pid) {
        pcntl_waitpid($pid, $status);
        /** @var array{ok: bool, value?: mixed, error?: string}|false $outcome */
        $outcome = unserialize((string) file_get_contents($files[$i]));
        @unlink($files[$i]);

        if ($outcome === false) {
            throw new RuntimeException("Concurrent task {$i} ended without a result.");
        }
        if (! $outcome['ok']) {
            throw new RuntimeException("Concurrent task {$i} failed — ".($outcome['error'] ?? 'unknown error'));
        }
        $results[$i] = $outcome['value'] ?? null;
    }

    return array_values($results);
}

/**
 * In the forked child: connect, wait for the shared start, run, write the outcome, and end WITHOUT running the test
 * runner's shutdown code (it belongs to the parent).
 *
 * @param  Closure(): mixed  $task
 */
function runChildTask(Closure $task, string $file, float $go): never
{
    try {
        DB::reconnect();
        DB::connection()->getPdo();
        while (microtime(true) < $go) {
            usleep(1000);
        }
        $outcome = ['ok' => true, 'value' => $task()];
    } catch (Throwable $e) {
        // As text: an exception's trace may hold closures, which do not serialise.
        $outcome = ['ok' => false, 'error' => $e::class.': '.$e->getMessage()];
    }

    try {
        file_put_contents($file, serialize($outcome));
    } catch (Throwable) {
        file_put_contents($file, serialize(['ok' => false, 'error' => 'The task\'s result could not be serialised.']));
    }
    DB::disconnect();
    posix_kill(posix_getpid(), SIGKILL);
    exit(1);
}
