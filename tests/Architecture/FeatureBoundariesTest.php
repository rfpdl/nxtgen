<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * Features orchestrate. Jobs hold the business logic. This is the guard that was missing.
 *
 * **Roy caught this by reading the code, which is the expensive way to find it.** *"i can see the app
 * is not following lucid principle properly"* — and he was right. Ten of twenty-six Features carried
 * direct Eloquent queries. Aegis, the reference project, has two in a hundred and ninety, both on an
 * explicit known-violations list.
 *
 * The Lucid principles are explicit that Jobs are the business logic layer, and `JobBoundariesTest`
 * next door already guards the Job side — no transactions, no cross-domain imports. **It guarded only
 * one direction.** Nothing said a Feature may not reach past its Jobs and query a model itself, so
 * the drift accumulated a Feature at a time with every gate green.
 *
 * The patterns below are aegis's, copied deliberately rather than reinvented, so the two projects
 * fail on the same things.
 *
 * **What a Feature IS allowed to do**, so this reads as a boundary rather than a prohibition:
 * run Jobs, wrap them in `DB::transaction`, `abort_if` on an HTTP condition, shape a response, and
 * manipulate collections the Jobs returned. Deciding *which* rows exist is the Jobs' job.
 */
/**
 * The concrete Features, as paths. The base classes are inherited, never run, and hold the accessors.
 *
 * @return list<string>
 */
function servedFeatureFiles(): array
{
    return array_values(array_filter(featureFiles(), fn (string $f): bool => ! str_starts_with(basename($f), 'Base')));
}

it('finds the Features it is supposed to be guarding', function (): void {
    // The guard on the guard. An empty file list would make every assertion below pass forever, and
    // this project has already shipped one vacuous gate.
    expect(servedFeatureFiles())->not->toBeEmpty();
});

it('never queries a model from a Feature', function (): void {
    /*
     * The one Roy spotted. A Feature that queries is a Feature that has absorbed a Job — and the
     * absorbed one is invisible: it cannot be reused by the next screen that needs the same rows, it
     * cannot be unit tested, and it does not appear in `ReachabilityTest`'s census of live units.
     *
     * `Get{Entity}ByIdJob` is the shape for resolving an id out of request input, copied from aegis's
     * `GetCompanyByIdJob`, which solves the same problem: a Feature has an id from a request body and
     * a Job needs a model.
     */
    /*
     * The pattern is shared with the Operation, Middleware and Tenancy guards (Helpers.php `modelEntryPattern()`),
     * so the four cannot drift apart — aegis's three copies did, and a `whereIn` passed in an Operation.
     */
    $offenders = [];

    foreach (servedFeatureFiles() as $file) {
        if (preg_match_all(modelEntryPattern(), codeOf($file), $matches) > 0) {
            $offenders[] = basename($file).': '.implode(', ', array_unique($matches[0]));
        }
    }

    expect($offenders)->toBe(
        [],
        "Eloquent belongs in a Job, not a Feature:\n  ".implode("\n  ", $offenders),
    );
});

it('never writes to a loaded model from a Feature', function (): void {
    // The same rule for the instance form, which the static pattern above cannot see. Persistence is
    // a Job's business whether the model arrived as a class or as a variable.
    $offenders = [];

    foreach (servedFeatureFiles() as $file) {
        if (preg_match_all(eloquentInstanceWritePattern(), codeOf($file), $matches) > 0) {
            $offenders[] = basename($file).': '.implode(', ', array_unique($matches[0]));
        }
    }

    expect($offenders)->toBe(
        [],
        "Writing a model belongs in a Job:\n  ".implode("\n  ", $offenders),
    );
});

it('uses no raw DB call from a Feature except a transaction', function (): void {
    /*
     * `DB::transaction` IS allowed here, and that is not a concession — plan §5.4 says it outright:
     * "Transactions wrap in the Feature or Operation, never in a Job." A Job cannot know what else
     * belongs in its transaction, and the Feature is the only unit that does. Aegis draws the line in
     * exactly the same place.
     */
    $offenders = [];

    foreach (servedFeatureFiles() as $file) {
        preg_match_all('/DB::(\w+)\(/', codeOf($file), $matches);

        $raw = array_values(array_unique(array_filter(
            $matches[1],
            fn (string $call): bool => $call !== 'transaction',
        )));

        if ($raw !== []) {
            $offenders[] = basename($file).': DB::'.implode(', DB::', $raw);
        }
    }

    expect($offenders)->toBe(
        [],
        "Raw DB belongs in a Job; only DB::transaction is allowed here:\n  ".implode("\n  ", $offenders),
    );
});

it('never calls one Feature from another', function (): void {
    // A Lucid principle verbatim: "Features shall not call other features". Two Features that need
    // the same work share a Job, and if the work spans domains it is an Operation.
    $offenders = [];

    foreach (servedFeatureFiles() as $file) {
        if (preg_match('/(new \w+Feature\(|\w+Feature::class)/', codeOf($file)) === 1) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([], 'Features must not run other Features: '.implode(', ', $offenders));
});
