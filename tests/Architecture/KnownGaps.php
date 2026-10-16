<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * Today's violations of the guards added in refactor batch 03, in one place — aegis's `KnownViolations`.
 *
 * **Every list may only SHRINK.** Each guard runs through `expectOnlyKnownGaps()`, which fails twice over: on a
 * violation that is not listed (new debt), and on a listed entry that no longer violates (paid-off debt left on
 * the list, where it would quietly exempt the next regression in that file). Fixing one of these means deleting
 * its line here in the same change. Adding a line is a deliberate, reviewable act — never the answer to a red
 * guard on new code.
 *
 * Grouped by the refactor batch that empties each list (`.planning/tasks/2026-10-17-refactor/spec.md`):
 * 04 renames, 05 groups → Levels, 06 enums / DTOs / generated TS, 07 Feature clean-up, 08 tests per Job and enum
 * and the rest. Batch 04 renamed classes to the GLOSSARY words (Church, Satellite) and rewrote the FQCNs here
 * without paying anything off; batch 05 deletes the `Group` entries with the classes.
 *
 * Keys: an FQCN where the rule is about a class, a path relative to `app/` (or a migration's filename) where it
 * is about a file.
 */
final class KnownGaps
{
    // ─── Batch 06 — enums, DTOs and generated TS ────────────────────────────────────────────────────────

    /**
     * Response DTOs with neither #[TypeScript] nor #[NonClientFacing] (DataObjectTest). Mostly internal hand-offs
     * — registry rows, registration payloads, list filters — that batch 06 marks #[NonClientFacing].
     *
     * @var list<string>
     */
    public const array DATA_NO_TYPESCRIPT = [
        'App\Domains\Auth\DataObjects\IssuedMagicLinkData',
        'App\Domains\Family\DataObjects\ChildDetailsData',
        'App\Domains\Family\DataObjects\ChildListFiltersData',
        'App\Domains\Family\DataObjects\FamilyListFiltersData',
        'App\Domains\Family\DataObjects\FamilyRegistrationData',
        'App\Domains\Family\DataObjects\GuardianDetailsData',
        'App\Domains\Registry\DataObjects\ApprovedChildRowData',
        'App\Domains\Registry\DataObjects\ImportResultData',
        'App\Domains\Registry\DataObjects\ProposedFamilyData',
        'App\Domains\Registry\DataObjects\QuarantinedRowData',
        'App\Domains\Registry\DataObjects\RegistryApprovalData',
        'App\Domains\Registry\DataObjects\RegistryProposalData',
        'App\Domains\Registry\DataObjects\RegistryRowData',
    ];

    /** @var list<string> {Model}Data without fromModel() (DataObjectTest) */
    public const array DATA_NO_FROM_MODEL = [];

    /** @var list<string> response DTOs and Inputs not declared final (DataObjectTest) */
    public const array DATA_NOT_FINAL = [];

    /** @var list<string> response DTOs exposing a `_path` (DataObjectTest) */
    public const array DATA_PATH_PROPS = [];

    /** @var list<string> response DTOs typed with DataCollection rather than a list (DataObjectTest) */
    public const array DATA_COLLECTION = [
        'App\Domains\Church\DataObjects\StaffMemberData',
    ];

    /** @var list<string> *Data classes declared outside a DataObjects folder (DataObjectTest) */
    public const array DATA_OUTSIDE_DATA_OBJECTS = [
        'App\Operations\Registration\RegistrationResultData',
    ];

    /**
     * Inputs without #[TypeScript] (DataObjectTest) — all of them; batch 06 generates them and deletes the
     * hand-written form types.
     *
     * @var list<string>
     */
    public const array INPUT_NO_TYPESCRIPT = [
        'App\Services\Auth\Data\PasswordLoginInput',
        'App\Services\Auth\Data\RequestMagicLinkInput',
        'App\Services\Auth\Data\UpdatePasswordInput',
        'App\Services\Church\Data\AddStaffInput',
        'App\Services\Church\Data\CorrectFamilyEmailInput',
        'App\Services\Church\Data\ResolveDuplicateInput',
        'App\Services\Church\Data\UpdateChildGroupInput',
        'App\Services\Church\Data\UpdateChildStatusInput',
        'App\Foundation\Inputs\ChildInput',
        'App\Foundation\Inputs\ChildPhotoInput',
        'App\Foundation\Inputs\FamilyDetailsInput',
        'App\Foundation\Inputs\GuardianDetailsInput',
        'App\Foundation\Inputs\GuardianInput',
        'App\Foundation\Inputs\RegisterFamilyInput',
    ];

    /** @var list<string> enums without #[TypeScript] (EnumTest) — all 14 */
    public const array ENUM_NO_TYPESCRIPT = self::ALL_ENUMS;

    /** @var list<string> enums not implementing HasEnumMetadata (EnumTest) — all 14; the concerns arrive in 06 */
    public const array ENUM_NO_METADATA = self::ALL_ENUMS;

    // ─── Batch 07 — Feature clean-up ────────────────────────────────────────────────────────────────────

    /**
     * Jobs taking a non-nullable `int $…Id` instead of the Model (JobConstructorTest) — 14 Jobs, 15 parameters
     * (`GetChurchChildJob` takes two). `CreateRoleAssignmentJob::$scopeId` is the polymorphic scope; batch
     * 04 decides whether that scope becomes a real relation (issue 04), and 07 whether it stays an id.
     *
     * @var list<string>
     */
    public const array JOB_ID_PARAMS = [
        'App\Domains\Church\Jobs\CreateRoleAssignmentJob',
        'App\Domains\Family\Jobs\GetChildWithFamilyJob',
        'App\Domains\Family\Jobs\GetChildrenJob',
        'App\Domains\Family\Jobs\GetChurchChildJob',
        'App\Domains\Family\Jobs\GetDuplicateCandidateJob',
        'App\Domains\Family\Jobs\GetFamiliesJob',
        'App\Domains\Family\Jobs\GetFamilyByIdJob',
        'App\Domains\Family\Jobs\GetFamilyChildJob',
        'App\Domains\Family\Jobs\GetMatchingChildJob',
        'App\Domains\Family\Jobs\GetOpenDuplicatesJob',
        'App\Domains\Family\Jobs\SearchFamiliesJob',
        'App\Domains\Group\Jobs\GetGroupJob',
        'App\Domains\Group\Jobs\GetGroupsJob',
        'App\Domains\User\Jobs\GetUserForUpdateJob',
    ];

    /** @var list<string> Job constructor properties that are not private readonly (JobConstructorTest) */
    public const array JOB_PROPS_NOT_PRIVATE_READONLY = [];

    /**
     * Operations querying a model directly (OperationBoundariesTest) — `Satellite::query()` (line ~50) and
     * `Group::query()` (line ~224).
     *
     * @var list<string>
     */
    public const array OPERATION_ELOQUENT = [
        'App\Operations\Registry\ApplyRegistryImportOperation',
    ];

    /** @var list<string> Operations writing a loaded model (OperationBoundariesTest) */
    public const array OPERATION_MODEL_WRITE = [];

    /**
     * Operations running another Operation (OperationBoundariesTest) — `AddChildToFamilyOperation` and
     * `RegisterFamilyOperation` from inside the import.
     *
     * @var list<string>
     */
    public const array OPERATION_RUNS_OPERATION = [
        'App\Operations\Registry\ApplyRegistryImportOperation',
    ];

    /** @var list<string> middleware querying a model directly (MiddlewareBoundariesTest) — `Guardian::query()` */
    public const array MIDDLEWARE_ELOQUENT = [
        'App\Services\Family\Http\Middleware\RequireGuardian',
    ];

    /**
     * Middleware calling `(new XJob(…))->handle()` by hand (MiddlewareBoundariesTest): `GetAccessScopeJob` in
     * `RequireAssignment`, and `GetLandingOptionsJob` in the shared `canSwitch` prop.
     *
     * @var list<string>
     */
    public const array MIDDLEWARE_RUNS_JOB_BY_HAND = [
        'App\Http\Middleware\HandleInertiaRequests',
        'App\Services\Church\Http\Middleware\RequireAssignment',
    ];

    /** @var list<string> Features not extending their own service's Base{Service}Feature (BaseClassTest) */
    public const array FEATURE_WRONG_BASE = [];

    // ─── Batch 08 — tests per Job and enum; ClassLength / PhpStyle / Migration lists emptied ────────────

    /**
     * Jobs with no `app/Domains/<Domain>/Tests/Jobs/<Job>Test.php` (TestCoverageTest) — 64 of 67. The three
     * with one: CreateChildPhotoJob, CreateFamiliesSpreadsheetJob, UpdateUserEmailVerifiedJob. Three listed here
     * (Create-, Redeem- and SendMagicLinkJob) ARE exercised, by the bundled `MagicLinkJobsTest`, which attributes
     * to none of them; GetRegistryProposalJob and GetRegistryRowsJob likewise by `ProposeRegistryFamiliesTest`.
     *
     * @var list<string>
     */
    public const array JOBS_WITHOUT_TESTS = [
        // Auth
        'CreateMagicLinkJob',
        'GetMagicLinkByTokenJob',
        'RedeemMagicLinkJob',
        'SendMagicLinkJob',
        'SendNoAccountEmailJob',
        'SignInUserJob',
        // Card
        'CreateQrCodeSvgJob',
        // Church
        'CreateRoleAssignmentJob',
        'GetAccessScopeJob',
        'GetChurchAdminCountJob',
        'GetLandingOptionsJob',
        'GetRoleAssignmentJob',
        'GetSatelliteJob',
        'GetSatellitesJob',
        'GetStaffRolesJob',
        'UpdateRoleAssignmentAsInactiveJob',
        // Family
        'CreateChildJob',
        'CreateDuplicateCandidatesJob',
        'CreateFamilyClaimJob',
        'CreateFamilyJob',
        'CreateGuardianJob',
        'CreateSystemNoteJob',
        'GetChildWithFamilyJob',
        'GetChildrenJob',
        'GetChurchChildJob',
        'GetDuplicateCandidateJob',
        'GetFamiliesJob',
        'GetFamilyByIdJob',
        'GetFamilyChildJob',
        'GetFamilyNotesJob',
        'GetFamilyPortalJob',
        'GetGuardianForUserJob',
        'GetMatchingChildJob',
        'GetOpenDuplicatesJob',
        'GetPendingClaimForFamilyJob',
        'GetPendingFamilyClaimJob',
        'SearchFamiliesJob',
        'UpdateChildDetailsJob',
        'UpdateChildGroupJob',
        'UpdateChildPhotoConsentJob',
        'UpdateChildStatusJob',
        'UpdateDuplicateCandidateStatusJob',
        'UpdateFamilyClaimStatusJob',
        'UpdateFamilyDetailsJob',
        'UpdateFamilyPrimaryGuardianJob',
        'UpdateGuardianDetailsJob',
        'UpdateGuardianUserJob',
        // Group (goes in batch 05)
        'GetGroupJob',
        'GetGroupsJob',
        'GetSuggestedGroupJob',
        // Registry
        'CreateImportMappingJob',
        'CreateRegistryReviewSpreadsheetJob',
        'GetApprovedRegistryRowsJob',
        'GetImportedSourceKeysJob',
        'GetRegistryProposalJob',
        'GetRegistryRowsJob',
        // User
        'CreateUserJob',
        'GetUserByCredentialsJob',
        'GetUserByEmailJob',
        'GetUserForUpdateJob',
        'GetUserIsFamilyOnlyJob',
        'GetUserIsStaffJob',
        'UpdateUserEmailJob',
        'UpdateUserPasswordJob',
    ];

    /** @var list<string> enums with no `tests/Unit/Enums/<Enum>Test.php` (TestCoverageTest) — all 14 */
    public const array ENUMS_WITHOUT_TESTS = [
        'AccessRowEnum',
        'ChildStatusEnum',
        'DuplicateReasonEnum',
        'DuplicateStatusEnum',
        'FamilyClaimStatusEnum',
        'GenderEnum',
        'MagicLinkPurposeEnum',
        'NoteKindEnum',
        'NoteVisibilityEnum',
        'RegistrationOutcomeEnum',
        'RegistrationSourceEnum',
        'RelationshipEnum',
        'RoleEnum',
        'ScopeTypeEnum',
    ];

    /**
     * Domain tests not named for the unit they sit beside (DomainTestLocationTest): a bundled Jobs test, a test
     * named for a behaviour, and AccessScope's test outside `Tests/DataObjects`.
     *
     * @var list<string>
     */
    public const array DOMAIN_TEST_NOT_MIRRORED = [
        'Domains/Auth/Tests/Jobs/MagicLinkJobsTest.php',
        'Domains/Church/Tests/AccessScopeTest.php',
        'Domains/Registry/Tests/Jobs/ProposeRegistryFamiliesTest.php',
    ];

    /** @var list<string> units over their layer's code-line budget (ClassLengthTest) — 165 of a Job's 120 */
    public const array UNITS_OVER_LAYER_LINE_LIMIT = [
        'Domains/Registry/Jobs/GetRegistryRowsJob.php',
    ];

    /** @var list<string> files without declare(strict_types=1) (PhpStyleTest) — Laravel's stub base controller */
    public const array PHP_NO_STRICT_TYPES = [
        'Http/Controllers/Controller.php',
    ];

    /** @var list<string> files using switch (PhpStyleTest) */
    public const array PHP_SWITCH = [];

    /** @var list<string> files using str_contains / str_starts_with / str_ends_with (PhpStyleTest) */
    public const array PHP_NATIVE_STR = [
        'Domains/Registry/DataObjects/ApprovedChildRowData.php',
        'Domains/Registry/Jobs/GetRegistryProposalJob.php',
        'Domains/Registry/Jobs/GetRegistryRowsJob.php',
        'Providers/E2EServiceProvider.php',
    ];

    /** @var list<string> files using array_find / array_any / array_all (PhpStyleTest) */
    public const array PHP_ARRAY_84 = [];

    /** @var list<string> files with a class constant that has no visibility (PhpStyleTest) */
    public const array PHP_CONST_VISIBILITY = [];

    /** @var list<string> migrations using a plain timestamp()/timestamps()/softDeletes() (MigrationTest) */
    public const array MIGRATION_PLAIN_TIMESTAMP = [
        // Laravel's own failed_jobs stub: `$table->timestamp('failed_at')->useCurrent()`.
        '0001_01_01_000002_create_jobs_table.php',
    ];

    /** @var list<string> */
    private const array ALL_ENUMS = [
        'App\Enums\AccessRowEnum',
        'App\Enums\ChildStatusEnum',
        'App\Enums\DuplicateReasonEnum',
        'App\Enums\DuplicateStatusEnum',
        'App\Enums\FamilyClaimStatusEnum',
        'App\Enums\GenderEnum',
        'App\Enums\MagicLinkPurposeEnum',
        'App\Enums\NoteKindEnum',
        'App\Enums\NoteVisibilityEnum',
        'App\Enums\RegistrationOutcomeEnum',
        'App\Enums\RegistrationSourceEnum',
        'App\Enums\RelationshipEnum',
        'App\Enums\RoleEnum',
        'App\Enums\ScopeTypeEnum',
    ];
}
