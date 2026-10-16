<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * Operations compose Jobs, the same way Features do — `FeatureBoundariesTest`'s rules carried one layer down, plus
 * aegis's `OperationArchTest` rules 10 and 11.
 *
 * An Operation exists for work that spans domains (plan §5.4), which makes it the most tempting place to "just
 * query": it already knows about families AND satellites. That is how `ApplyRegistryImportOperation`
 * came to look satellites (and, until batch 05, groups) up itself. The rows an Operation needs come from Jobs, so they are reusable,
 * unit tested, and counted by `ReachabilityTest`.
 *
 * Allowed, as in a Feature: run Jobs, wrap them in `DB::transaction` (plan §5.4 — "Transactions wrap in the
 * Feature or Operation, never in a Job"), take a lock, shape a result.
 *
 * Not ported: aegis's QueueableOperation serialisation rules (14, 15). No Operation here is queued; port them
 * with the first one that is.
 */
it('finds the Operations it is supposed to be guarding', function (): void {
    expect(count(operationFiles()))->toBeGreaterThan(2);
});

it('never queries a model from an Operation', function (): void {
    expectOnlyKnownGaps(
        fqcnsMatching(operationFiles(), modelEntryPattern()),
        KnownGaps::OPERATION_ELOQUENT,
        'Eloquent belongs in a Job, not an Operation',
    );
});

it('never writes to a loaded model from an Operation', function (): void {
    expectOnlyKnownGaps(
        fqcnsMatching(operationFiles(), eloquentInstanceWritePattern()),
        KnownGaps::OPERATION_MODEL_WRITE,
        'Writing a model belongs in a Job',
    );
});

it('uses no raw DB call from an Operation except a transaction', function (): void {
    $offenders = [];

    foreach (operationFiles() as $file) {
        preg_match_all('/DB::(\w+)\s*\(/', codeOf($file), $m);

        if (array_diff($m[1], ['transaction']) !== []) {
            $offenders[] = pathToFqcn($file);
        }
    }

    expect($offenders)->toBe([], 'Raw DB belongs in a Job; only DB::transaction here: '.implode(', ', $offenders));
});

it('never runs one Operation from another', function (): void {
    /*
     * Aegis rule 10, and the Lucid principle behind it: an Operation composes Jobs. Two Operations that share
     * work share the Jobs; nesting them hides a transaction inside a transaction and makes the call graph the
     * thing a reader has to hold. `RegisterFamilyOperation` and `AddChildToFamilyOperation` are run from inside
     * `ApplyRegistryImportOperation` today — batch 07 unpicks that.
     */
    $offenders = [];

    foreach (operationFiles() as $file) {
        $own = basename($file, '.php');

        if (preg_match_all('/\b(\w+Operation)(::class|\s*\()/', codeOf($file), $m) > 0 && array_diff($m[1], [$own]) !== []) {
            $offenders[] = pathToFqcn($file);
        }
    }

    expectOnlyKnownGaps($offenders, KnownGaps::OPERATION_RUNS_OPERATION, 'Operations compose Jobs, not other Operations');
});

it('never reaches for a Feature from an Operation', function (): void {
    // The dependency points one way: Features run Operations, never the reverse.
    $offenders = fqcnsMatching(operationFiles(), '/\b\w+Feature(::class|\s*\()/');

    expect($offenders)->toBe([], 'Operations must not know about Features: '.implode(', ', $offenders));
});

it('runs Jobs through run(), never ->handle() by hand', function (): void {
    // `$this->run()` is what makes the call typed (stubs/Lucid.stub) and visible as a unit; a hand-called
    // handle() skips both.
    $offenders = fqcnsRunningJobsByHand(operationFiles());

    expect($offenders)->toBe([], 'Use $this->run(new XJob(…)): '.implode(', ', $offenders));
});
