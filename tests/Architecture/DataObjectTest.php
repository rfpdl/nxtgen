<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Attributes\NonClientFacing;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The shape of the two kinds of DTO, ported from aegis's `DataObjectArchTest` (rules 12–17, 26–30).
 *
 * - A **response DTO** (`App\Domains\X\DataObjects\*Data`) is what a Job or Operation hands back. It is final,
 *   built `fromModel()` when it mirrors a model, and carries `#[TypeScript]` so the page's props are generated —
 *   unless it never reaches a browser, which it says with `#[NonClientFacing]`. Exactly one of the two.
 * - An **Input** (`App\Services\X\Data\*Input`, or a shared one in `App\Foundation\Inputs`) is what a request
 *   carries in. Final, never `fromModel()`.
 *
 * **One deliberate difference from aegis** (DECISIONS 2026-10-17): Inputs ALSO carry `#[TypeScript]`. Aegis
 * forbids it; here the form a page posts is typed from the same class that validates it, so the hand-written
 * `ChildForm` / `FamilyFields` / `GuardianFields` types went in batch 06.
 *
 * `#[NonClientFacing]` is aegis's `App\Foundation\Attributes\NonClientFacing`, added in batch 06.
 */
const NON_CLIENT_FACING = NonClientFacing::class;

/** @return list<ReflectionClass<object>> concrete response DTOs */
function responseDtos(): array
{
    // A file that does not load is not skipped silently: BaseClassTest fails on it.
    return array_values(array_filter(classesIn(dataObjectFiles()), fn (ReflectionClass $r): bool => ! $r->isAbstract()));
}

/** @return list<ReflectionClass<object>> concrete Inputs */
function inputDtos(): array
{
    // A file that does not load is not skipped silently: BaseClassTest fails on it.
    return array_values(array_filter(classesIn(inputFiles()), fn (ReflectionClass $r): bool => ! $r->isAbstract()));
}

it('finds the DTOs it is supposed to be guarding', function (): void {
    expect(count(responseDtos()))->toBeGreaterThan(20);
    expect(count(inputDtos()))->toBeGreaterThan(10);
});

it('gives every response DTO #[TypeScript] unless it is #[NonClientFacing]', function (): void {
    $missing = array_map(
        fn (ReflectionClass $r): string => $r->getName(),
        array_values(array_filter(
            responseDtos(),
            fn (ReflectionClass $r): bool => $r->getAttributes(NON_CLIENT_FACING) === [] && $r->getAttributes(TypeScript::class) === [],
        )),
    );

    expectOnlyKnownGaps($missing, KnownGaps::DATA_NO_TYPESCRIPT, 'Add #[TypeScript] (or #[NonClientFacing] if no page reads it)');
});

it('never gives a #[NonClientFacing] DTO #[TypeScript] as well', function (): void {
    // The mirror rule: the marker makes an omission deliberate, so having both is a contradiction.
    $both = array_map(
        fn (ReflectionClass $r): string => $r->getName(),
        array_values(array_filter(
            responseDtos(),
            fn (ReflectionClass $r): bool => $r->getAttributes(NON_CLIENT_FACING) !== [] && $r->getAttributes(TypeScript::class) !== [],
        )),
    );

    expect($both)->toBe([], 'Both #[NonClientFacing] and #[TypeScript]: '.implode(', ', $both));
});

it('builds a {Model}Data with fromModel()', function (): void {
    // Only DTOs named for a model — an aggregate (`FamilyPortalData`) or a filter has no model to come from.
    $models = array_flip(modelNames());

    $missing = array_map(
        fn (ReflectionClass $r): string => $r->getName(),
        array_values(array_filter(
            responseDtos(),
            fn (ReflectionClass $r): bool => isset($models[(string) preg_replace('/Data$/', '', $r->getShortName())]) && ! $r->hasMethod('fromModel'),
        )),
    );

    expectOnlyKnownGaps($missing, KnownGaps::DATA_NO_FROM_MODEL, 'A {Model}Data is built with a static fromModel()');
});

it('declares every response DTO and Input final', function (): void {
    $open = array_map(
        fn (ReflectionClass $r): string => $r->getName(),
        array_values(array_filter([...responseDtos(), ...inputDtos()], fn (ReflectionClass $r): bool => ! $r->isFinal())),
    );

    expectOnlyKnownGaps($open, KnownGaps::DATA_NOT_FINAL, 'Declare the DTO final');
});

it('gives every Input #[TypeScript] (unlike aegis)', function (): void {
    $missing = array_map(
        fn (ReflectionClass $r): string => $r->getName(),
        array_values(array_filter(inputDtos(), fn (ReflectionClass $r): bool => $r->getAttributes(TypeScript::class) === [])),
    );

    expectOnlyKnownGaps($missing, KnownGaps::INPUT_NO_TYPESCRIPT, 'Add #[TypeScript] so the page posting it is typed from it');
});

it('never builds an Input fromModel()', function (): void {
    // An Input is what the request says, not what the database holds; a fromModel() is a response DTO in disguise.
    $offenders = array_map(
        fn (ReflectionClass $r): string => $r->getName(),
        array_values(array_filter(inputDtos(), fn (ReflectionClass $r): bool => $r->hasMethod('fromModel'))),
    );

    expect($offenders)->toBe([], 'Inputs with fromModel(): '.implode(', ', $offenders));
});

it('keeps response DTOs and Inputs in their own folders', function (): void {
    $misplaced = [];

    foreach (phpFiles('Services') as $file) {
        if (str_contains($file, '/Data/') && str_ends_with($file, 'Data.php')) {
            $misplaced[] = relativePath($file).' — a response DTO belongs in Domains/X/DataObjects';
        }
    }

    foreach (phpFiles('Domains') as $file) {
        if (str_contains($file, '/DataObjects/') && str_ends_with($file, 'Input.php')) {
            $misplaced[] = relativePath($file).' — an Input belongs in Services/X/Data';
        }
    }

    expect(array_values(array_unique($misplaced)))->toBe([], implode("\n", array_unique($misplaced)));
});

it('keeps every *Data class in a DataObjects folder', function (): void {
    // A DTO declared beside the Operation that returns it is invisible to every rule above.
    $outside = [];

    foreach (phpFiles('', '*Data.php') as $file) {
        if (! str_contains($file, '/DataObjects/')) {
            $outside[] = pathToFqcn($file);
        }
    }

    expectOnlyKnownGaps($outside, KnownGaps::DATA_OUTSIDE_DATA_OBJECTS, 'Move the DTO into Domains/X/DataObjects');
});

it('never names an Input Store… or Save…', function (): void {
    $offenders = array_values(array_filter(
        array_map(fn (string $f): string => basename($f, '.php'), inputFiles()),
        fn (string $name): bool => preg_match('/^(Store|Save)[A-Z]/', $name) === 1,
    ));

    expect($offenders)->toBe([], 'Use Create…/Update…: '.implode(', ', $offenders));
});

it('types nested DTO lists as arrays, not DataCollection', function (): void {
    expectOnlyKnownGaps(
        fqcnsMatching(dataObjectFiles(), '/\\bDataCollection(Of)?\\b/'),
        KnownGaps::DATA_COLLECTION,
        'Use /** @var list<XData> */ array',
    );
});

it('never exposes a storage path to the page', function (): void {
    // `children.photo_path` is private storage; a page gets a signed `_url` (DECISIONS 2026-10-16, photos).
    $offenders = [];

    foreach (responseDtos() as $dto) {
        foreach ($dto->getConstructor()?->getParameters() ?? [] as $param) {
            if (preg_match('/(_path|Path)$/', $param->getName()) === 1) {
                $offenders[] = $dto->getName().'::$'.$param->getName();
            }
        }
    }

    expectOnlyKnownGaps($offenders, KnownGaps::DATA_PATH_PROPS, 'Expose a signed _url, never a _path');
});
