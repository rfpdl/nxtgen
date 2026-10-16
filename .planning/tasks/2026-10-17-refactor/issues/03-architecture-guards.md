# 03 — Architecture guards from aegis

Status: ready-for-agent
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
