# 08 — Empty the known-gaps lists + user stories

Status: resolved
Blocked by: 07

## What
- A unit test per Job (`Domains/X/Tests/Jobs/{Job}Test.php`) and per Enum — TestCoverage known gaps → empty.
- `#[UserStory]` on every Feature (aegis style), numbered `story#1…`; tests name their story; per-service
  `STORIES.md` holds domain knowledge only (as aegis).
- ClassLength / PhpStyle / Migration known gaps → empty.

## Comments

**2026-10-17 — done (Claude).** `make check` All green (738 Pest tests); `make e2e` 43 passed.

- **Tests per unit**: 55 Job tests and 13 enum tests (`tests/Unit/Enums`, where aegis keeps them), real DB through
  `World`; every church-taking loader proves it skips another church's rows. `MagicLinkJobsTest` split into
  Create-/Redeem-/SendMagicLinkJobTest; `ProposeRegistryFamiliesTest` into GetRegistryRowsJobTest and
  GetRegistryProposalJobTest (`git rm`, staged); `AccessScopeTest` `git mv`'d to `Tests/DataObjects`.
- **Code, behaviour-preserving**: `str_*` → `Str::` (Registry, E2EServiceProvider); `strict_types` on the base
  Controller; `failed_jobs.failed_at` → `timestampTz` (edited in place, pre-deploy); `GetRegistryRowsJob` 165 → 70
  code lines by moving the CSV column positions and cell parsing into `RegistryCsvRowData` (DTO, with its own test).
- **Stories**: `#[UserStory(title, persona, happyPaths, unhappyPaths)]` on all 46 Features, `story#1`–`story#83`;
  tests carry `@story#N` in their title (aegis's form). `StoryTokenTest` (port of `StoryTokenArchTest`) gates it.
  Per-service `STORIES.md` hold domain rules only. One new Pest test for `ShowChurchHomeFeature` (story#42).
- **Deviations from aegis**: no `test:catalog` allocator, `.story-index` or `make qa-test-list` — numbers are taken by
  hand (next after the highest); the guard adds "every Feature has a story" and "every story has a test" since no
  catalog reports coverage. `module`/`labels` dropped (one module per service, no Jira); `persona` required.
- **KnownGaps**: every list empty; file regrouped by guard, mechanism kept.
- **Found, not fixed** (follow-ups): door-search phone match strips the search's non-digits but not the stored
  phone's (`Family::scopeMatchingContact`), so "+971 50 123 4567" is not found by "501234567" — fixed separately.
  `UpdateUserPasswordJob` does not bump `credential_version`: by design (plan §4.3), recorded as SECURITY #20
  ('could'). _Correction:_ the first report said `RegistrationOutcomeEnum::guardianMessage()` is unused — wrong, it
  is used by `RegisterFamilyFeature` (Public).
- **`DuplicateReasonEnum::SameEmail` removed**: nothing created it; a second registration with a known email goes
  through the claim path (plan §4.2). Migration comment and generated TS updated.

**2026-10-17 — code review fixes (Claude).**
- `UserStory` docblock and DECISIONS clarify the claim form is `@story#N` in a test title. `StoryTokenTest` now reads
  tokens only from Pest `it`/`test`/`describe` and Playwright `test` titles, comments stripped, with a probe test.
- The CSV parser is a value object, not a DTO: `Domains/Registry/Values/RegistryCsvRecord` with an int-backed
  `RegistryCsvColumn` enum (private cells), a `GuardianName` value, and `SheetCell` (trim / blank-is-null / yes)
  shared with `ApprovedChildRowData`. `Values/` follows aegis (`Domains/*/Values`). `RegistryCsvColumn` lives outside
  `app/Enums`, so it carries no `#[TypeScript]`/metadata; the TS generator now takes only `#[TypeScript]` enums
  (`AttributedEnumTransformer`), so it stays out of `generated.d.ts`.
- `substr` → `Str::substr` in the parser and `GetRegistryRowsJob`.
- Reviewer and decision notes ("Codex diff review", "review B2", "Roy 2026-10-16") moved from test titles into a
  comment above each test; titles state the behaviour.
- Birth-month case kept in the parser and proposal tests only; two tautologies replaced with literals; stories #1,
  #20, #23 and #81 reworded as specific behaviour.
