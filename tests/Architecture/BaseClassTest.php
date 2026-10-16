<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Jobs\BaseJob;
use Lucid\Units\Operation;
use ReflectionClass;

/**
 * The base classes, ported from aegis's `BaseClassArchTest` (rules 19a, 19b) and extended to say every unit
 * EXTENDS its base — which in this codebase is what types `run()` and `serve()` (stubs/Lucid.stub), and, for a
 * Feature, what gives it the service's `church()` / `scope()` / `family()` accessors.
 *
 * Also the home of the check that every file the reflection-based guards read actually LOADS: those guards skip
 * a file with no class to reflect, and without the test below that skip would be silent.
 */
it('finds the units it is supposed to be guarding', function (): void {
    expect(count(jobFiles()))->toBeGreaterThan(10);
    expect(count(featureFiles()))->toBeGreaterThan(20);
    expect(count(operationFiles()))->toBeGreaterThan(2);
});

it('loads a class from every file the reflection guards read', function (): void {
    $files = [...jobFiles(), ...featureFiles(), ...operationFiles(), ...dataObjectFiles(), ...inputFiles(), ...enumFiles()];

    expect(unloadable($files))->toBe([], "No class at the name the path gives — every reflection guard skips these:\n  "
        .implode("\n  ", unloadable($files)));
});

it('keeps BaseJob abstract', function (): void {
    expect((new ReflectionClass(BaseJob::class))->isAbstract())->toBeTrue();
});

it('keeps every Base…Feature abstract', function (): void {
    $concrete = array_map(
        fn (ReflectionClass $c): string => $c->getName(),
        array_values(array_filter(classesIn(featureFiles()), fn (ReflectionClass $c): bool => str_starts_with($c->getShortName(), 'Base') && ! $c->isAbstract())),
    );

    expect($concrete)->toBe([], 'Declare abstract: '.implode(', ', $concrete));
});

it('has every Job extend BaseJob', function (): void {
    $offenders = array_map(
        fn (ReflectionClass $c): string => $c->getName(),
        array_values(array_filter(classesIn(jobFiles()), fn (ReflectionClass $c): bool => ! $c->isSubclassOf(BaseJob::class))),
    );

    expect($offenders)->toBe([], 'Extend App\Foundation\Jobs\BaseJob: '.implode(', ', $offenders));
});

it('has every Operation extend Lucid\'s Operation', function (): void {
    $offenders = array_map(
        fn (ReflectionClass $c): string => $c->getName(),
        array_values(array_filter(classesIn(operationFiles()), fn (ReflectionClass $c): bool => ! $c->isSubclassOf(Operation::class))),
    );

    expect($offenders)->toBe([], 'Extend Lucid\Units\Operation: '.implode(', ', $offenders));
});

it('has every Feature extend its own service\'s Base…Feature (or one of that service\'s own bases)', function (): void {
    $offenders = [];

    foreach (classesIn(featureFiles()) as $class) {
        if ($class->isAbstract()) {
            continue;
        }

        $service = explode('\\', $class->getName())[2];
        $base = "App\\Services\\{$service}\\Features\\Base{$service}Feature";
        $parent = $class->getParentClass();

        // Or a route the service's middleware does not guard gets its own abstract Base…Feature inside the same
        // service (Family's BaseChildPhotoFeature: the photo link, opened by parents AND staff, outside
        // RequireGuardian). Deliberate, and narrow: the base must live in this service's Features and be abstract.
        $ownServiceBase = $parent !== false && $parent->isAbstract()
            && str_starts_with($parent->getShortName(), 'Base')
            && str_starts_with($parent->getName(), "App\\Services\\{$service}\\Features\\");

        if (! (class_exists($base) && $class->isSubclassOf($base)) && ! $ownServiceBase) {
            $offenders[] = $class->getName();
        }
    }

    expectOnlyKnownGaps($offenders, KnownGaps::FEATURE_WRONG_BASE, 'Extend the service\'s Base{Service}Feature');
});
