<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * The guards' known violations, in one place — aegis's `KnownViolations`. **Every list is empty**: refactor
 * batches 03–08 paid off all of them.
 *
 * Kept as the mechanism for the next guard. Each guard runs through `expectOnlyKnownGaps()`, which fails on a
 * violation that is not listed (new debt) AND on a listed entry that no longer violates (paid-off debt left
 * behind, which would quietly exempt the next regression). So a new guard over existing code may start with a
 * list here, and that list may only shrink. Adding a line for new code is never the answer to a red guard.
 *
 * Keys: an FQCN where the rule is about a class, a path relative to `app/` (or a migration's filename) where it
 * is about a file.
 */
final class KnownGaps
{
    // DataObjectTest
    /** @var list<string> response DTOs with neither #[TypeScript] nor #[NonClientFacing] */
    public const array DATA_NO_TYPESCRIPT = [];

    /** @var list<string> {Model}Data without fromModel() */
    public const array DATA_NO_FROM_MODEL = [];

    /** @var list<string> response DTOs and Inputs not declared final */
    public const array DATA_NOT_FINAL = [];

    /** @var list<string> response DTOs exposing a `_path` */
    public const array DATA_PATH_PROPS = [];

    /** @var list<string> response DTOs typed with DataCollection rather than a list */
    public const array DATA_COLLECTION = [];

    /** @var list<string> *Data classes declared outside a DataObjects folder */
    public const array DATA_OUTSIDE_DATA_OBJECTS = [];

    /** @var list<string> Inputs without #[TypeScript] */
    public const array INPUT_NO_TYPESCRIPT = [];

    // EnumTest
    /** @var list<string> enums without #[TypeScript] */
    public const array ENUM_NO_TYPESCRIPT = [];

    /** @var list<string> enums not implementing HasEnumMetadata */
    public const array ENUM_NO_METADATA = [];

    // JobConstructorTest
    /**
     * Jobs taking a non-nullable `int $…Id` instead of the Model. A lookup Job's own `$id` (GetFamilyByIdJob…) is
     * the id it resolves, as in aegis's GetCompanyByIdJob, and is not one.
     *
     * @var list<string>
     */
    public const array JOB_ID_PARAMS = [];

    /** @var list<string> Job constructor properties that are not private readonly */
    public const array JOB_PROPS_NOT_PRIVATE_READONLY = [];

    // OperationBoundariesTest, MiddlewareBoundariesTest, BaseClassTest
    /** @var list<string> Operations querying a model directly */
    public const array OPERATION_ELOQUENT = [];

    /** @var list<string> Operations writing a loaded model */
    public const array OPERATION_MODEL_WRITE = [];

    /** @var list<string> Operations running another Operation */
    public const array OPERATION_RUNS_OPERATION = [];

    /** @var list<string> middleware querying a model directly */
    public const array MIDDLEWARE_ELOQUENT = [];

    /** @var list<string> middleware calling `(new XJob(…))->handle()` by hand */
    public const array MIDDLEWARE_RUNS_JOB_BY_HAND = [];

    /** @var list<string> Features not extending their own service's Base{Service}Feature */
    public const array FEATURE_WRONG_BASE = [];

    // TestCoverageTest, DomainTestLocationTest
    /** @var list<string> Jobs with no `app/Domains/<Domain>/Tests/Jobs/<Job>Test.php` */
    public const array JOBS_WITHOUT_TESTS = [];

    /** @var list<string> enums with no `tests/Unit/Enums/<Enum>Test.php` */
    public const array ENUMS_WITHOUT_TESTS = [];

    /** @var list<string> domain tests not named for the unit they sit beside */
    public const array DOMAIN_TEST_NOT_MIRRORED = [];

    // ClassLengthTest
    /** @var list<string> units over their layer's code-line budget */
    public const array UNITS_OVER_LAYER_LINE_LIMIT = [];

    // PhpStyleTest
    /** @var list<string> files without declare(strict_types=1) */
    public const array PHP_NO_STRICT_TYPES = [];

    /** @var list<string> files using switch */
    public const array PHP_SWITCH = [];

    /** @var list<string> files using str_contains / str_starts_with / str_ends_with */
    public const array PHP_NATIVE_STR = [];

    /** @var list<string> files using array_find / array_any / array_all */
    public const array PHP_ARRAY_84 = [];

    /** @var list<string> files with a class constant that has no visibility */
    public const array PHP_CONST_VISIBILITY = [];

    // MigrationTest
    /** @var list<string> migrations using a plain timestamp()/timestamps()/softDeletes() */
    public const array MIGRATION_PLAIN_TIMESTAMP = [];
}
