# 07 — Feature clean-up

Status: ready-for-agent
Blocked by: 06

## What
- Base features: `scopedChild($id, ability)` and `church()` on the church-staff base; `guardian()` on the family
  base — replacing the repeated load-then-`abort_unless` in 6 + 3 features.
- One rule for history notes: Jobs write them (today consent writes in a Job, status/group/email in the Feature).
- One way to add a child: `RegisterFamilyOperation:113-124` reuses `AddChildToFamilyOperation`.
- Operations query only through Jobs (`ApplyRegistryImportOperation`); Middleware goes through Jobs
  (`RequireGuardian`, `RequireAssignment`).
- Request attributes (`'organization'`, `'scope'`, `'family'`) behind typed getters, not strings.
- Move `ShowChildPhotoFeature` out of the Auth service to where it belongs.
- Job-constructor known-gaps list emptied.
