# 05 — Astra review fixes (roll call)

Status: resolved
Raised: Astra (Codex adversarial review, gpt-6-astra) over a637172..365f099, 2026-10-28 night. Verdict: needs-attention.

## What
1. **Undo can leave a concurrent present mark stored** (`UpdateCheckInAsUndoneJob`). Undo loads an unmarked
   Check-in, another request marks it present, Undo saves: Eloquent leaves out present_at/present_by because null
   equals their loaded values, so the row is undone AND marked. Fix: one explicit church-scoped UPDATE that always
   writes undone_at/undone_by AND present_at = present_by = NULL together. Regression test: a presence write lands
   between loading and undoing.
2. **A late tap can mark a revived Check-in present** (`UpdateCheckInPresenceJob`). Revival after Undo reuses the
   same row id, so a delayed tap from before the Undo succeeds after the re-check-in, possibly in another Squad.
   Fix: an integer `revision` on check_ins, bumped on every revival; Who's in sends it; the present PUT carries it;
   the UPDATE is conditioned on id + revision + undone_at IS NULL. A stale revision gets the same "not checked in
   any more" answer, or "Amara was checked in again, tap again" (Claude's pick). Test: an old tap arriving after
   Undo and re-check-in is refused.

## Done when (what you see)
Nothing changes on screen in normal use; a tap from before an Undo + re-check-in never ticks the new Check-in.

## Test seams
Job tests with deterministic interleavings (the two orderings above); Feature test for the stale-revision PUT;
allowlist + types for `revision`; existing e2e stays green.

## Comments

**2026-10-28 — built (agent), not committed.** `make check` green (Pest 1832 + arch 173, Vitest 398, PHPStan L10) and
`make e2e` green (85 passed, 1 skipped as before). Nothing changes on screen in normal use.

1. **Undo vs a concurrent mark**: UpdateCheckInAsUndoneJob is one church-scoped UPDATE that always writes undone_at,
   undone_by AND present_at = present_by = NULL (then syncs the model). Job test: a mark lands between loading and
   undoing → the row ends undone and unmarked. Red against the old code (save() skipped the unchanged nulls).
2. **Late tap vs a revived Check-in**: new `check_ins.revision` (`2026_10_29_000100_add_revision_to_check_ins`,
   unsigned, default 0), bumped by CreateCheckInJob's revive (under its locking read). UpdateCheckInPresenceJob's
   UPDATE names id + church + revision + `undone_at IS NULL`. Who's in sends each child's `revision`
   (WhosInChildData, allowlisted, generated types); the tap sends the revision it showed (useRollCall.mark,
   WhosInSquadCard's `present` emit; Vitest); MarkPresentInput requires it. A stale revision on a Check-in that is in
   again answers "Amara was checked in again — please tap again." (otherwise "… is not checked in any more.").
   Job test: tap → Undo → re-check-in → the old tap is refused, the row stays unmarked at revision + 1. Revert-checked
   twice: without the revision condition the test fails, and without the bump on revive it fails too. Feature test:
   the stale PUT is refused with that message; a PUT with the new revision marks it. The access grid's PUT carries the
   revision.

