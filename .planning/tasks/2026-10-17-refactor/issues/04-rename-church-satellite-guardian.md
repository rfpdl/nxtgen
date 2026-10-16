# 04 — Rename to Church / Satellite / Guardian

Status: ready-for-agent
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
