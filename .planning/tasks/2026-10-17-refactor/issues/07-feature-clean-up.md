# 07 — Feature clean-up

Status: resolved
Blocked by: 06

## What
- Base features: `scopedChild($id, ability)` and `church()` on the church-staff base; `guardian()` on the family
  base — replacing the repeated load-then-`abort_unless` in 6 + 3 features.
- One rule for history notes: Jobs write them (today consent writes in a Job, status/group/email in the Feature).
- One way to add a child: `RegisterFamilyOperation:113-124` and `AddChildToFamilyOperation` share the same Jobs
  (suggest a Level, create the child) — an Operation never runs another Operation (aegis; guarded since 03).
- Unpick `ApplyRegistryImportOperation`, which runs RegisterFamily/AddChildToFamily Operations and queries models
  directly: the command (or a Feature) orchestrates, the Operations share Jobs. Empties
  `OPERATION_RUNS_OPERATION` and the Operation model-query gap.
- Middleware goes through Jobs and `$this->run()`-style calls, not `->handle()` by hand: `RequireGuardian`,
  `RequireAssignment`, `HandleInertiaRequests` (its `canSwitch` prop).
- Request attributes (`'organization'`, `'scope'`, `'family'`) behind typed getters, not strings.
- Move `ShowChildPhotoFeature` out of the Auth service to where it belongs.
- Job-constructor known-gaps list emptied.

## Comments

**2026-10-17 — done (batch 07).** `make check` All green (164 arch, 510 Pest, 114 Vitest); `make e2e` 43 passed.
Every batch-07 KnownGaps list is empty; no gap added. Behaviour and screens unchanged (same 404s, notes, flashes).

What changed:

- **Base Features.** `BaseChurchFeature` gained `scopedChild($id, $allows)` and `scopedFamily($id, $allows)`: load
  within the church, then 404 unless the closure allows it. Called as
  `$this->scopedChild($this->childId, $this->scope()->canEditFamily(...))`. That replaced the load-then-`abort_unless`
  in 8 Features (4 child, 4 family). `UpdateChildLevelFeature` and `AddChildFeature` still check the scope *before*
  loading (their rule does not depend on the family), so the order of 404s stays the same. `BaseFamilyFeature` has
  `guardian()`, `family()` (the guardian's), `ownChild($id)` and `guardianUser()`. Gone from the family Features:
  `request()->user()` + abort, and the second `GetGuardianForUserJob`. Covered by `BaseChurchFeatureTest`.
- **Request attributes** live behind `Foundation/Support/RequestContext` (`putChurch/church`, `putScope/scope`,
  `putGuardian/guardian`). Nothing else spells an attribute name; `RouteChurch` reads through it too. The `'family'`
  attribute became `'guardian'`, and the family comes from the guardian.
- **History notes: the Job that makes the change writes the note** (via `Note::system`), the way
  `UpdateChildPhotoConsentJob` already did: `UpdateChildStatusJob` (only when the status changed),
  `UpdateChildLevelJob`, `UpdateDuplicateCandidateStatusJob` (on the new family) and `UpdateUserEmailJob` (now takes
  `family` + `by`). The note text is the same, and the Feature's `DB::transaction` still wraps both. Nothing ran
  `CreateSystemNoteJob` any more, so it is deleted.
- **One way to add a child / no Operation runs an Operation.** `ApplyRegistryImportOperation` became
  `Services/Console/Features/Registry/ApplyRegistryImportFeature`, served from `nxtgen:registry-apply` through
  Lucid's `ServesFeatures`, which is aegis's pattern (`PollMailboxCommand` → `PullMailboxEmailsFeature`). It runs
  `RegisterFamilyOperation` and then `AddChildToFamilyOperation` per child, which share the Level-suggestion and
  create-child Jobs. The `Satellite::query()` became `GetSatellitesJob` + `GetSatelliteJob`, and the approval file is
  read inside the Feature. `RegistryImportTest` is unchanged and green.
- **Middleware** uses Lucid's `UnitDispatcher` and `$this->run(...)`: `RequireAssignment` (GetAccessScopeJob),
  `RequireGuardian` (GetGuardianForUserJob, which now eager-loads `family.church` in place of the inline query), and
  `HandleInertiaRequests` (`can_switch`).
- **Jobs take Models.** `int $churchId` became `Church $church` in 8 Jobs. `GetUserForUpdateJob(User)` re-reads and
  locks. `CreateRoleAssignmentJob` takes the scope model (`Church|Satellite $scope`) in place of
  `scopeType` + `scopeId`. A lookup Job's own `int $id` stays, as in aegis's `GetCompanyByIdJob`.
- **`ShowChildPhotoFeature` moved to the Family service** (`Features/Photo`, its own `routes/photos.php`, same URL
  and route name). The photo belongs to the family and is shown only with its Guardian's consent. It sits outside
  `/{church}/family` because staff open it too.
- Job tests added: CreateRoleAssignment, GetGuardianForUser, UpdateChildStatus, UpdateDuplicateCandidateStatus,
  UpdateUserEmail, plus a note case on UpdateChildLevel. Those names came off `JOBS_WITHOUT_TESTS`.

Design choices and differences from aegis:

- A new **Console** service, named like Public/Family/Church for who it serves: the operator at the terminal. It has
  no routes, no AccessRow and no request context. A Church Feature would have needed an access-matrix row that no
  staff member holds.
- Aegis middleware *does* call `(new XJob)->handle()` by hand. Our guard is stricter, so ours uses `UnitDispatcher`.
- The duplicate answer wording ("not the same family" / "the same family, flagged for merging") appears in the Job
  (note) and again in the Feature (flash). This was kept rather than adding an enum method ahead of batch 08's enum
  tests.
- `RegistryApplyCommand` still finds the church with `Church::query()`, as before. No guard covers commands, and aegis
  commands query the same way.

**2026-10-17 — code-review fixes (standards axis).** `make check` All green (164 arch, 522 Pest, 114 Vitest);
`make e2e` 43 passed; `RegistryImportTest` untouched.

1. **Console service** recorded in `docs/DECISIONS.md` 2026-10-17 (Claude — for Roy to confirm).
2. **Photo link base.** `ShowChildPhotoFeature` now extends a new `Family/Features/Photo/BaseChildPhotoFeature`,
   whose `viewer()` holds the one `request()->user()` + 404. It was a Refused Bequest on `BaseFamilyFeature`.
   `BaseClassTest`'s rule now also accepts an abstract `Base…Feature` that lives in the Feature's own service; the
   rule was widened in the test itself, with no gap added.
3. **Role scopes** use one `Domains/Church/DataObjects/RoleScope` (type + id) everywhere: `of(Church|Satellite)`,
   `churchIdOf()` and `ofAssignment()`. `AccessScope::canGrant`, `StaffRoleData::label`, `AddStaffInput::scope()`,
   the People Features and `CreateRoleAssignmentJob(user, role, place, grantedBy)` all take it. The Job takes the
   church from the place, so a satellite role cannot land in another church. Schedule (Release 2) is a change to
   `RoleScope` only. `RoleScopeTest` was written first.
4. **Import Feature.** The new `GetSatellitesByNameJob` returns the satellite models keyed by name; they are reused
   per family, with no reload. `handle()` is split into `applyFamily`, `rowsToLoad`, `register` and
   `registration`. **Not done: moving the row → DTO mapping into the Registry domain.** The "never lets one Domain
   import another" guard forbids a Registry DataObject from building Family DTOs, so the mapping stays in the Feature
   as named methods.
5. **`UpdateFamilyEmailJob`** (Family domain: family, login, email, by) replaces `UpdateUserEmailJob`, which is
   deleted. No User-domain Job knows about families.
6. **The one deliberate behaviour change:** `UpdateChildLevelJob` no longer writes a note when the Level is
   unchanged. That is the same rule as status. The test failed first.
7. **`Foundation/Support/EmailAddress::normalise()`** has a unit test and replaces `mb_strtolower(trim(...))` for
   emails in 9 files. The registry sheet readers lower-case without trimming, so they are unchanged.
8. **`DuplicateStatusEnum::answer()`** is the one wording, used by the note and the flash. It has an enum test, and
   the enum is off `ENUMS_WITHOUT_TESTS`.
