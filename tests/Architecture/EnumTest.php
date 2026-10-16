<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use ReflectionEnum;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use UnitEnum;

/**
 * Every enum is generated to TypeScript and describes itself the same way — ported from aegis's `EnumArchTest`.
 *
 * Decided 2026-10-17: enums carry `#[TypeScript]` and aegis's `App\Enums\Concerns` (`HasEnumMetadata` interface,
 * `ProvidesEnumMetadata` trait), so a page gets `{value, label, color}` rows from one place instead of each enum
 * hand-writing its own `toFrontend()`. Batch 06 ported the concerns and gave them to every enum.
 */
function hasEnumMetadataInterface(): string
{
    return HasEnumMetadata::class;
}

function providesEnumMetadataTrait(): string
{
    return ProvidesEnumMetadata::class;
}

/** @return list<ReflectionEnum<UnitEnum>> */
function appEnums(): array
{
    $enums = [];

    // A file that does not load is not skipped silently: BaseClassTest fails on it.
    foreach (classesIn(enumFiles()) as $reflection) {
        if ($reflection->isEnum()) {
            /** @var class-string<UnitEnum> $name */
            $name = $reflection->getName();
            $enums[] = new ReflectionEnum($name);
        }
    }

    return $enums;
}

/** @param ReflectionEnum<UnitEnum> $enum */
function hasEnumMetadata(ReflectionEnum $enum): bool
{
    return in_array(hasEnumMetadataInterface(), $enum->getInterfaceNames(), true);
}

it('finds the enums it is supposed to be guarding', function (): void {
    expect(count(appEnums()))->toBeGreaterThan(10);
});

it('generates every enum to TypeScript', function (): void {
    $missing = array_map(
        fn (ReflectionEnum $e): string => $e->getName(),
        array_values(array_filter(appEnums(), fn (ReflectionEnum $e): bool => $e->getAttributes(TypeScript::class) === [])),
    );

    expectOnlyKnownGaps($missing, KnownGaps::ENUM_NO_TYPESCRIPT, 'Add #[TypeScript] to the enum');
});

it('has every enum implement HasEnumMetadata', function (): void {
    $missing = array_map(
        fn (ReflectionEnum $e): string => $e->getName(),
        array_values(array_filter(appEnums(), fn (ReflectionEnum $e): bool => ! hasEnumMetadata($e))),
    );

    expectOnlyKnownGaps($missing, KnownGaps::ENUM_NO_METADATA, 'Implement HasEnumMetadata (use ProvidesEnumMetadata)');
});

it('never uses ProvidesEnumMetadata without implementing HasEnumMetadata', function (): void {
    // The trait supplies toFrontend(); the interface is what callers type against. One without the other is an
    // enum that looks described and cannot be passed where a described enum is expected.
    $offenders = array_map(
        fn (ReflectionEnum $e): string => $e->getShortName(),
        array_values(array_filter(
            appEnums(),
            fn (ReflectionEnum $e): bool => in_array(providesEnumMetadataTrait(), $e->getTraitNames(), true) && ! hasEnumMetadata($e),
        )),
    );

    expect($offenders)->toBe([], 'Uses ProvidesEnumMetadata, does not implement HasEnumMetadata: '.implode(', ', $offenders));
});

it('gives a value, label and color for every case of a HasEnumMetadata enum', function (): void {
    expect(appEnums())->not->toBeEmpty();

    foreach (appEnums() as $enum) {
        if (! hasEnumMetadata($enum)) {
            continue;
        }

        $toFrontend = [$enum->getName(), 'toFrontend'];
        $rows = is_callable($toFrontend) ? $toFrontend() : null;

        expect($rows)->toBeArray()->toHaveCount(count($enum->getCases()), "{$enum->getShortName()}::toFrontend() has one row per case");

        foreach (is_array($rows) ? $rows : [] as $row) {
            expect($row)->toBeArray()->toHaveKeys(['value', 'label', 'color']);
            expect(is_array($row) ? $row['label'] : null)->not->toBe('', "{$enum->getShortName()} has an empty label");
            expect(is_array($row) ? $row['color'] : null)->not->toBe('', "{$enum->getShortName()} has an empty color");
        }
    }
});
