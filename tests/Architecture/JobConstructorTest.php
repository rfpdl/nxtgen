<?php

declare(strict_types=1);

namespace Tests\Architecture;

use ReflectionClass;
use ReflectionNamedType;

/**
 * What a Job takes and what it may not do — the rest of aegis's `JobArchTest` (rules 5b, 7, 8, 9, 36) that
 * `JobBoundariesTest` did not already cover (it keeps the naming and nesting rules; this file, what a Job takes).
 *
 * **Jobs take Models, not ids** (DECISIONS 2026-10-17). A Job handed `int $familyId` has to look the family up
 * again — a second query, and a second place the church scope can be forgotten — and its caller has usually
 * just loaded that very model. The Feature resolves the id once (a `Get{Entity}ByIdJob`) and passes the model on.
 * As in aegis, a NULLABLE `?int $…Id` is an optional association and is not flagged.
 */

/** @return list<ReflectionClass<object>> concrete Jobs */
function concreteJobs(): array
{
    // A file that does not load is not skipped silently: BaseClassTest fails on it.
    return array_values(array_filter(classesIn(jobFiles()), fn (ReflectionClass $r): bool => ! $r->isAbstract()));
}

it('finds the Jobs it is supposed to be guarding', function (): void {
    expect(count(concreteJobs()))->toBeGreaterThan(10);
});

it('passes a Job models, not int ids', function (): void {
    $offenders = [];

    foreach (concreteJobs() as $job) {
        foreach ($job->getConstructor()?->getParameters() ?? [] as $param) {
            $type = $param->getType();

            if (preg_match('/Ids?$/', $param->getName()) === 1
                && $type instanceof ReflectionNamedType
                && $type->getName() === 'int'
                && ! $type->allowsNull()) {
                $offenders[] = $job->getName();

                break;
            }
        }
    }

    expectOnlyKnownGaps($offenders, KnownGaps::JOB_ID_PARAMS, 'Take the Model, not int $…Id — resolve the id once in the Feature');
});

it('keeps a Job\'s constructor properties private readonly', function (): void {
    // A Job is a value: built once with what it needs, then run. A public or mutable property is a second way in.
    $offenders = [];

    foreach (concreteJobs() as $job) {
        foreach ($job->getConstructor()?->getParameters() ?? [] as $param) {
            if (! $param->isPromoted()) {
                continue;
            }

            $property = $job->getProperty($param->getName());

            if (! $property->isPrivate() || ! $property->isReadOnly()) {
                $offenders[] = $job->getName().'::$'.$param->getName();
            }
        }
    }

    expectOnlyKnownGaps($offenders, KnownGaps::JOB_PROPS_NOT_PRIVATE_READONLY, 'Declare it private readonly');
});

it('never names another Job from inside a Job', function (): void {
    // JobBoundariesTest refuses `new XJob(` and `$this->run(`; this closes the `XJob::class` door (a container
    // call, or a dispatch by name) the same way aegis's rule 5b does.
    $offenders = [];

    foreach (jobFiles() as $file) {
        $own = basename($file, '.php');

        if (preg_match_all('/\b(\w+Job)::class/', codeOf($file), $m) > 0 && array_diff($m[1], [$own, 'BaseJob']) !== []) {
            $offenders[] = $own;
        }
    }

    expect($offenders)->toBe([], 'Jobs must not reference other Jobs: '.implode(', ', $offenders));
});

it('never dispatches an event or a queued job from a Job', function (): void {
    // A side effect the caller cannot see or order inside its transaction. Events and queueing belong to the
    // Feature or Operation that knows the transaction has committed.
    $offenders = [];

    foreach (jobFiles() as $file) {
        if (preg_match('/\b(\w+::dispatch(Sync|If|Unless)?|event|dispatch)\s*\(|Event::dispatch\s*\(/', codeOf($file)) === 1) {
            $offenders[] = basename($file, '.php');
        }
    }

    expect($offenders)->toBe([], 'Dispatch from the Feature or Operation, not a Job: '.implode(', ', $offenders));
});
