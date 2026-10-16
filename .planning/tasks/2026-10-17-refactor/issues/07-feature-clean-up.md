# 07 — Feature clean-up

Status: ready-for-agent
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
