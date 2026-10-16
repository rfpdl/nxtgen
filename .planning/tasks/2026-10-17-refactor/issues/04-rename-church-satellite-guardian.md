# 04 — Rename to Church / Satellite / Guardian

Status: resolved
Blocked by: 03

## What (GLOSSARY.md)
- `Organization` → `Church` (model, table, `organization_id` → `church_id`, `Domains/Organization`, the `Org`
  service, `RouteOrganization`, `BaseOrgFeature`, `ScopeTypeEnum::Organization`, `AccessScope`…).
- `Branch` → `Satellite` (`branches`, `home_branch_id` → `home_satellite_id`, `BranchOptionData`, `ScopeTypeEnum::Branch`,
  `NXTGEN_SATELLITES` already right).
- `$parent` → `$guardian` in code; "parent" stays on screen.
- "volunteer" only in parent-facing words.
- Edit the original migrations in place; `migrate:fresh`.
- Add the missing FK on `role_assignments.scope_id` if the scope design allows (it's polymorphic — otherwise note why).

## Done when
`grep -rniE 'organi[sz]ation|branch' app resources/js routes database tests` finds only deliberate leftovers (listed
here); behaviour unchanged; `make check` + `make e2e` green.

## Comments

**2026-10-17 — done.** Organization → Church, Branch → Satellite across app, database, routes, tests, e2e and
resources/js: models, tables (`churches`, `satellites`), every `organization_id` → `church_id`,
`home_branch_id` → `home_satellite_id`, `Domains/Church`, `Services/Church` (`BaseChurchFeature`,
`ChurchServiceProvider`, `ShowChurchHomeFeature`), `RouteChurch`, `GetChurchChildJob`, `GetSatellite(s)Job`,
`SatelliteOptionData`, `ScopeTypeEnum::Church|Satellite` (stored `church|satellite`), request attribute and route
param `church`, route names `church.*`, shared prop `churchNav`, `ChurchShell`, `pages/Church/*`, factories,
seeders, test files `Church*Test`. Original migrations edited in place (`…_create_churches_tables.php`).
`$parent` → `$guardian` (or `$guardianUser` / `$guardianName` where a `$guardian` already existed);
`familyWithGuardian`, `guardianMessage()`, `ChildPhotoForm who="guardian"`, e2e `Guardian` type and
`FAMILIES.*.guardian`. "volunteer" kept only in what people see; code, comments and test names say staff member.
URLs, screen text, emails, flash messages, export names and cookies are unchanged. Generated TS regenerated.

**`role_assignments.scope_id` FK: not added.** The column is polymorphic: `scope_type` says whether it points at
`churches`, `satellites` or (R2) event series, so no single foreign key fits. Kept as an id checked in code
(`AccessScope::canGrant` + `GetSatelliteJob` within the church); batch 07 decides whether the Job keeps taking an id.

**Deliberate leftovers** (`grep -rniE 'organi[sz]ation|\bbranch' app resources/js routes database tests config`):
- `config/sentry.php` — "organization ID" comments and `org_id` belong to the Sentry SDK's own config.

Also kept (outside that grep, on purpose):
- `nxtgen:registry-apply --org=<slug>` — the operator-facing CLI option (README/STATE document it); renaming it
  would change how the command is run.
- `parent_notes` / `parentNotes` (the child's "Anything else we should know") — Laravel builds the validation
  message from the field name, so renaming it would change on-screen text ("The parent notes field…"). Batch 06
  can rename it with an explicit attribute name when it regenerates Inputs.
- `tests/e2e/parent/` folder and `RegistrationOutcomeEnum`/`RegistrationSourceEnum` labels and prose comments
  that say "parent" — journeys and words as people say them.
- Registry sheet headers (`Parent email`, `Home satellite`) — the external Google Sheet's column names.

Note: the working dev database still has the old table names; `make fresh` (destructive) rebuilds it.
- 2026-10-17 code-review fixes: `--org` → `--church` on `nxtgen:registry-apply` (nothing deployed, so no
  operator habit to keep; STATE updated); `GetRegistryRowsJob::PARENT` → `GUARDIAN`; leftover `$b`/`$o`/`$otherOrg`
  names. One unreachable message changes: `home_satellite_id`'s default `integer` message now names the new field —
  the dropdown never sends a non-integer, so no person can see it.
