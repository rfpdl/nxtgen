<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * Middleware decides who may pass; Jobs decide which rows exist. `FeatureBoundariesTest`'s rule carried to
 * `app/Http/Middleware` and each service's `Http/Middleware`.
 *
 * Middleware is where this project's church scope is DECIDED (`RequireGuardian`, `RequireAssignment`), which is
 * the strongest reason for it to ask a Job: the query that says "this user is a guardian in this church" is then
 * one unit, tested once, and the same one the next screen reuses. A query written inline in middleware is a
 * second copy of an access rule that no Job test covers.
 *
 * A Job is run through `$this->run()` (Lucid's `UnitDispatcher` trait), not by hand — `(new XJob(…))->handle()`,
 * `app(XJob::class)->handle()`, or a `$job` variable handled later (Helpers.php `runsJobByHand()`, whose limits
 * are written there; GuardPatternsTest proves each form). The dispatcher is what types the call
 * (stubs/Lucid.stub) and makes it a unit like any other.
 */
it('finds the middleware it is supposed to be guarding', function (): void {
    expect(count(middlewareFiles()))->toBeGreaterThan(2);
});

it('never queries a model from middleware', function (): void {
    expectOnlyKnownGaps(
        fqcnsMatching(middlewareFiles(), modelEntryPattern()),
        KnownGaps::MIDDLEWARE_ELOQUENT,
        'Ask a Job; middleware never queries',
    );
});

it('never writes to a model from middleware', function (): void {
    $offenders = fqcnsMatching(middlewareFiles(), eloquentInstanceWritePattern());

    expect($offenders)->toBe([], 'Writing a model belongs in a Job: '.implode(', ', $offenders));
});

it('uses no raw DB call from middleware', function (): void {
    $offenders = fqcnsMatching(middlewareFiles(), '/DB::\w+\s*\(/');

    expect($offenders)->toBe([], 'Raw DB belongs in a Job: '.implode(', ', $offenders));
});

it('runs Jobs through run(), never ->handle() by hand', function (): void {
    expectOnlyKnownGaps(
        fqcnsRunningJobsByHand(middlewareFiles()),
        KnownGaps::MIDDLEWARE_RUNS_JOB_BY_HAND,
        'Use Lucid\'s UnitDispatcher and $this->run(new XJob(…))',
    );
});

it('never reaches for a Feature or an Operation from middleware', function (): void {
    $offenders = [];

    foreach (middlewareFiles() as $file) {
        if (preg_match('/\b\w+(Feature|Operation)(::class|\s*\()/', codeOf($file)) === 1) {
            $offenders[] = pathToFqcn($file);
        }
    }

    expect($offenders)->toBe([], 'Middleware must not run Features or Operations: '.implode(', ', $offenders));
});
