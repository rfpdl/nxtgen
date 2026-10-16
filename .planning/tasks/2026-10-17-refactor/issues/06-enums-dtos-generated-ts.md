# 06 — Enums, DTOs and generated TS

Status: resolved
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

## Comments

**2026-10-17 — done.** Nothing people see changed; `make check` and `make e2e` (43/43) green.

- **Enums.** `app/Enums/Concerns` ported from aegis (`HasEnumMetadata`, `ProvidesEnumMetadata`, `HasDefault`). All
  14 enums carry `#[TypeScript]`, implement `HasEnumMetadata` and use the trait; the hand-written `toFrontend()`
  copies are gone (the trait's adds `color`). `color()` is a semantic token where it means something (statuses,
  claim/duplicate states) and `muted` elsewhere. `default()` on ChildStatus (active), DuplicateStatus (open),
  FamilyClaimStatus (pending), NoteVisibility (staff) — read by the migrations' column defaults, the factories,
  `CreateChildJob`, `CreateFamilyClaimJob`, `CreateDuplicateCandidatesJob`, `Note::system` and the import.
  `AccessRowEnum::rule()` (unused) became its `label()`. `enumFiles()` now skips `Enums/Concerns`.
- **Enum options on the page (aegis's way).** A Feature shares `'enums' => ['gender' => GenderEnum::toFrontend()]`;
  the component reads `useEnum('gender')` (typed by `EnumMap` → the generated enum). The `genders` /
  `relationships` / `options.statuses` props went, and `ChildAdminControls`' hard-coded status list now comes from
  `enums.child_status` (same values, labels, order).
- **DTOs.** `#[NonClientFacing]` added (`App\Foundation\Attributes`) and put on the 14 internal hand-offs;
  `RegistrationResultData` moved to `Domains/Family/DataObjects`; `StaffMemberData` lost `DataCollectionOf`. New
  `ChurchData` (fromModel) and `Foundation/DataObjects/PaginationData` (`ListPage::meta()` returns it).
- **snake_case.** Response DTO properties are declared snake_case, as aegis does (no name mapper). Fields that carry
  an enum's *label* are named for it: `status_label`, `relationship_label`, `reason_label`; `ChildPortalData.gender`
  is now the `GenderEnum` value. Shared props: `auth.can_switch`, `churchNav.can_manage_people|…`.
- **Inputs** all carry `#[TypeScript]`; `UploadedFile` generates as `File`. The hand-written `ChildForm`,
  `GuardianFields`, `FamilyFields` and the three local `Option` types are deleted: forms are `Draft<XInput>`
  (`resources/js/types` — blank `''`, unanswered yes/no `null`), every `useForm` is typed from its Input.
- **One `church` prop**, shared lazily by `HandleInertiaRequests` from `RouteChurch` (`RequireGuardian` now puts the
  church on the request too); the ten Feature copies and page declarations are gone. Pages and `ChurchShell` read
  it with `useChurch()`. `ShowRegisterThanksFeature` takes nothing now (the route binding still 404s).
- **One `Option<T>`** and **one `Pagination`** type (`@/types`; Pagination generated).
- **`route()`** in `SwitchLink`, `ChurchShell`, `Choose`, `Password` and `ConfirmLink`.
- **`parent_notes` → `guardian_notes`** (column, model, DTOs, Inputs, Vue). Laravel's message is pinned word for
  word: `ChildInput::attributes()` (`guardian_notes` → "parent notes") for the flat forms, and
  `RegisterFamilyInput::messages()` `children.*.guardian_notes.max` → "The children.:index.parent notes field must
  not be greater than :max characters." for registration (a nested Data's attribute name wins over its parent's, so
  the nested case needs the message). Both pinned by Pest tests.
- KnownGaps batch-06 lists emptied; the inert EnumTest note is gone.

**Code-review round (2026-10-17).**
- Casing rule logged in DECISIONS 2026-10-17 (Claude — for Roy to confirm): top-level prop names camelCase,
  everything inside snake_case. So the shared `church_nav` went back to `churchNav` (its fields stay snake_case),
  and the internal DTOs (`#[NonClientFacing]`: ChildDetailsData, FamilyRegistrationData, the list filters, the
  registry rows…) are snake_case too.
- `MagicLinkRouteEnum` (`#[TypeScript]`, login | verify | claim) is the one map from a link's stored purpose to
  its address and its POST route: the email builds `route('auth.magic.confirm', …)` from it, the route's
  `whereIn` reads `segments()`, the controller binds it, and ConfirmLink gets `purpose` + the `action` URL from
  the server (its words are a `Record<MagicLinkRoute, …>`, so a new purpose does not typecheck without them).
- ChildAdminControls picks through typed `v-model`s instead of casting the event; `useEnum` lost its cast and its
  unused `labelFor` (a saved value's label comes as a `…_label` DTO field — one way).
- The list filters are a generated `ListFiltersData` (was the hand-written `RegistrationListFilterValues`).
- `FamilyFormEnums::toFrontend()` is the one builder of `relationship` + `gender` for the four family-form pages.
- Validation pins: a wildcard attribute name cannot reproduce the old nested text (ChildInput's own attribute for
  the same key wins, and must say "parent notes" for the add/edit forms), so `RegisterFamilyInput::messages()`
  keeps `children.*.guardian_notes.max` and now `.string` too, with `:index`; all four messages pinned by tests.
- Notes: the `enums` rows now carry `color` (from the trait), which no screen uses yet. A parent's family page
  gets its `church` from the address; that is safe because RequireGuardian has already tied the signed-in
  guardian to that church (404 otherwise).

**Deviations from aegis.**
- Inputs generate TS (DECISIONS 2026-10-17).
- Top-level *page* prop names stay camelCase (`grantOptions`, `canSetLevel`, `registerUrl`, `hasPassword`): they
  are Vue component props, which `vue/prop-name-casing` requires camelCase — aegis pages do the same
  (`jobOrder`). Top-level shared props follow the same rule (`churchNav`); everything inside a prop is
  snake_case.
- No `extraMeta()` on the trait: no enum needs one yet.
- Still hand-written, deliberately: `SharedProps` (mirrors `HandleInertiaRequests::share`, an array not a DTO).
