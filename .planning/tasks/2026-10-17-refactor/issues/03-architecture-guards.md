# 03 — Architecture guards from aegis

Status: resolved
Blocked by: 02

## What
Port from `~/projects/work/aegis/aegis/core/tests/Architecture`, each with a known-gaps list that may only shrink:
- PhpStyle (strict_types, no switch, `Str::` over `str_*`, const visibility) — today: `GetRegistryRowsJob`,
  `ApprovedChildRowData`, `E2EServiceProvider`, `app/Http/Controllers/Controller.php`.
- DataObject (response DTOs final, `fromModel()`, `#[TypeScript]` unless `#[NonClientFacing]`; Inputs `#[TypeScript]` too).
- Enum (`#[TypeScript]`, HasEnumMetadata/ProvidesEnumMetadata).
- Migration (timestampTz, comments, UTC).
- ClassLength.
- TestCoverage (a test per Job and per Enum; known gaps = today's untested Jobs).
- Job constructors take Models, not `int $…Id` (15 today).
- Boundary guards also cover **Operations** (no direct `Model::query()` — `ApplyRegistryImportOperation:50,224`; no
  Operation running an Operation) and **Middleware** (`RequireGuardian:30`, `RequireAssignment` calling `->handle()`).
- TODO #19: TenancyArchTest blind to `DB::table()`/`->from()`/joins; ReachabilityTest matches basenames not FQCNs.

## Done when
Every guard is in `tests/Architecture`, green, with its known-gaps list; TODO #17, #19 closed.

## Comments

**2026-10-17 — built (Claude).** All new guards share `tests/Architecture/Helpers.php` (namespaced, required from
`tests/Pest.php`) and one gap list, `tests/Architecture/KnownGaps.php`, grouped by the batch that empties it. Every
ratcheted rule goes through `expectOnlyKnownGaps()`: it fails on an unlisted violation AND on a listed entry that
no longer violates (checked by mutation). No app code changed — the PhpStyle four are listed, not fixed.

Guards added (gap list → size):
- `PhpStyleTest` — strict types (`PHP_NO_STRICT_TYPES` 1: `Http/Controllers/Controller.php`), no switch (0),
  `Str::` over `str_*` (`PHP_NATIVE_STR` 4: `ApprovedChildRowData`, `GetRegistryProposalJob`, `GetRegistryRowsJob`,
  `E2EServiceProvider`), no `array_find/any/all` (0), const visibility (0). Providers are NOT exempt (aegis exempts).
- `DataObjectTest` — response DTO `#[TypeScript]` unless `#[NonClientFacing]` (`DATA_NO_TYPESCRIPT` 13), never both,
  `{Model}Data::fromModel()` (0), final (0), Inputs `#[TypeScript]` (`INPUT_NO_TYPESCRIPT` 14 — all), no Input
  `fromModel()`, folders, `*Data` only in DataObjects (`DATA_OUTSIDE_DATA_OBJECTS` 1: `RegistrationResultData`), no
  Store/Save Inputs, no DataCollection (`DATA_COLLECTION` 1: `StaffMemberData`), no `_path` props (0).
  `NonClientFacing` is named by string until 06 adds it.
- `EnumTest` — `#[TypeScript]` (`ENUM_NO_TYPESCRIPT` 14), `HasEnumMetadata` (`ENUM_NO_METADATA` 14), trait ⇒
  interface, value/label/color rows (live from 06). Concerns named by string until 06 ports them.
- `MigrationTest` — Tz spellings (`MIGRATION_PLAIN_TIMESTAMP` 1: Laravel's jobs stub), app timezone UTC,
  `->comment()` before the FK chain (statement-level).
- `ClassLengthTest` — aegis's per-layer code-line budgets unchanged (`UNITS_OVER_LAYER_LINE_LIMIT` 1:
  `GetRegistryRowsJob`, 165/120). `ApplyRegistryImportOperation` is 174/180.
- `TestCoverageTest` — `Domains/X/Tests/Jobs/<Job>Test.php` per Job (`JOBS_WITHOUT_TESTS` 64 of 67),
  `tests/Unit/Enums/<Enum>Test.php` per enum (`ENUMS_WITHOUT_TESTS` 14).
- `DomainTestLocationTest` — no stray dirs; a domain test mirrors its unit (`DOMAIN_TEST_NOT_MIRRORED` 3:
  `MagicLinkJobsTest`, `AccessScopeTest`, `ProposeRegistryFamiliesTest`).
- `JobConstructorTest` — Models not `int $…Id` (`JOB_ID_PARAMS` 14 Jobs / 15 params), promoted props private
  readonly (0), no `OtherJob::class`, no event/queue dispatch.
- `OperationBoundariesTest` — no model queries (`OPERATION_ELOQUENT` 1), no model writes (0), raw DB only
  `transaction`, no Operation running an Operation (`OPERATION_RUNS_OPERATION` 1), no Features, no `->handle()` by hand.
  All gaps are `ApplyRegistryImportOperation`.
- `MiddlewareBoundariesTest` — covers `app/Http/Middleware` and `Services/*/Http/Middleware`: no model queries
  (`MIDDLEWARE_ELOQUENT` 1: `RequireGuardian`), no writes, no raw DB, no `(new XJob)->handle()`
  (`MIDDLEWARE_RUNS_JOB_BY_HAND` 2: `RequireAssignment` and — found here — `HandleInertiaRequests`' `canSwitch`),
  no Features/Operations.
- `BaseClassTest` — BaseJob and `Base*Feature` abstract; every Job extends BaseJob, every Operation Lucid's
  Operation, every Feature its own service's `Base{Service}Feature` (`FEATURE_WRONG_BASE` 0).

TODO #19: `TenancyArchTest` now also sees `DB::table()`, `->from()`, every `join` form (aliases included) and static
queries without `query()` (`Family::find`), accepts table-qualified `organization_id`/`family_id`, and has a dataset
proving each form is caught. One new deliberate cross-church entry: `RedeemMagicLinkJob`'s join to `family_claims`
(it re-checks the claim the link names). `ReachabilityTest` now resolves `new X(` / `X::class` to FQCNs through each
file's namespace and imports, so the four same-named Feature pairs are told apart (with a test for it).

Not ported, and why: Audit, QueueConfig, SheetPreviewData (aegis-only), StoryToken (batch 08); aegis's REST-verb Feature naming (Features keep domain verbs); QueueableOperation serialisation rules
(no queued Operations yet); aegis's "Inputs must not have #[TypeScript]" (reversed by DECISIONS 2026-10-17);
`MeasureClassLengthsJob` (the count lives in the test helpers — no app Job just for a test); aegis's
`migrations-audit`/`-logs` dirs. A per-Feature test-coverage rule is left to batch 08's user stories.

Note for batch 04: renames will rewrite FQCNs in `KnownGaps` without paying anything off.

`make check`: All green (arch 108, pest 393, vitest 106); after the review fixes, arch 162, pest 447, vitest 106.

**2026-10-17 — code-review fixes (Claude).**
- One shared model-entry pattern (`Helpers.php modelEntryPattern()`) for the Feature, Operation, Middleware and
  Tenancy guards: any static call on a model except a short allow-list of non-queries (factory, event hooks,
  `DuplicateCandidate::familyOf`), fully or partly qualified names included. FeatureBoundariesTest's and Tenancy's
  own lists are gone. `GuardPatternsTest` proves each form (with(), all(), latest(), scopes, `\App\…\Family::`).
- Tenancy: either quote; `self::`/`static::` queries inside a tenant model's file; `Note::system()` trusted as
  scoped inside Note.php (`modelHelpersScopedInside()`, with an honesty test); a qualified scope column counts only
  on the table (or alias) the query reached. What it still cannot see is written in the test: joinSub/fromSub,
  `DB::select`/raw SQL, names in variables, builders spread across statements.
- PhpStyle catches `\str_contains(` / `\array_find(` (dataset); route-file docblock corrected.
- Job-run-by-hand also catches `app(XJob::class)->handle()`, `resolve`/`make`, and `$job = new XJob; $job->handle()`.
- BaseClassTest fails on any guarded file that does not load (reflection guards skip such files), plus a sentinel.
- New files' helpers and constants live in the `Tests\Architecture` namespace; FeatureBoundariesTest and
  JobBoundariesTest now use the shared `featureFiles()`/`jobFiles()` and the tokeniser `stripComments`.
- Reachability's same-name test uses a real pair (Org vs Family `AddChildFeature`) via `referencesIn()`.
- Coverage comments corrected (3 Jobs file-tested; MagicLinkJobsTest exercises 3, ProposeRegistryFamiliesTest 2).
- EnumTest's `toFrontend` check is labelled inert until batch 06.
- **MorphMap ported, adapted** (`MorphMapTest`) — it was wrongly called aegis-only: enforcement stays on, every
  alias resolves to a concrete model, and every alias hard-coded against a morph `*_type` column is real (a typo
  matches no rows silently). Aegis's "every model mapped or excluded" rule is not copied because
  `enforceMorphMap()` already throws on an unmapped model at write time.
