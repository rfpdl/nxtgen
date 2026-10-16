# 06 — Enums, DTOs and generated TS

Status: ready-for-agent
Blocked by: 05

## What
- Enums: `#[TypeScript]` + aegis `Concerns/` (HasEnumMetadata, HasDefault, ProvidesEnumMetadata); drop hand-written
  `toFrontend()`.
- DTOs: final, `fromModel()`, `#[TypeScript]` / `#[NonClientFacing]`; Inputs generate TS — delete hand-written
  `ChildForm`, `GuardianFieldset`/`FamilyFieldset` types.
- **snake_case** props everywhere (generated TS and pages).
- One shared Inertia `church` prop (today built in 10 Features, declared in 10 pages).
- One `Option` type and one pagination type.
- `route()` instead of hard-coded URLs (`SwitchLink.vue:17`, `OrgShell.vue:58`, `Choose.vue:17`, `Password.vue:66`).
- Known-gaps lists for DataObject/Enum guards emptied.
