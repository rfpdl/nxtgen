# 02 — Confirm a Squad's head count

Status: resolved
Blocked by: 01

## What
- Under each Squad on Who's in: a number field (pre-filled with the last confirmed head count, else the number
  checked in — Claude, after review) and **"Confirm head
  count"**. `PUT /{church}/classes/{schedule}/squads/{squad}/head-count` with `{count: int 0–200}` — today's Session
  of that class must exist, the Squad must have a Check-in in it, same access as the door.
- New table `squad_head_counts` (session_id, squad_id, head_count, counted_at, counted_by; unique session+squad).
  Confirming again replaces it (latest wins, no history). Two leaders confirming at once → one row, no 500
  (savepoint + locking read, like CreateCheckInJob).
- Under the button: "✓ Confirmed 13:20 by Joy (4)" (church time, the staff member's first name).
- **Mismatch warning** when the confirmed count ≠ checked in now: "Checked in 5, counted 4." and, when some children
  are marked present, "Not marked: Isabel, Mia" (the checked-in children not marked). Recomputed on every load, so a
  later Check-in or Undo shows it too.

## Done when (what you see)
A Lifeshaper types 2 for Level 2 Girls (3 checked in, Amara and Sofia ticked), confirms, and sees "✓ Confirmed …
(2)" and the warning "Checked in 3, counted 2. Not marked: Isabel". Confirming 3 clears the warning.

## Test seams
Feature: access grid row, allowlist, validation (0–200, integer), no Session / Squad not in it → 404, other church.
Jobs: upsert latest wins; two-connection test (two confirms at once → one row). E2E: the Done-when journey.

## Comments

**2026-10-28 — built (agent), not committed.** `make check` green (Pest 1770 + arch 173, Vitest 375, PHPStan L10, build,
migrate-check) and `make e2e` green (78 passed, 1 skipped as before; the Who's in journey now confirms a head count, on
iPhone Safari, Android Chrome and desktop Chrome). Looked at on a 390px phone and at 1280; one fix (below).

- **Schema** (`2026_10_28_000200_create_squad_head_counts_table`): church_id, session_id, squad_id, head_count
  (0–200), counted_at, counted_by (users, null on delete); unique (session_id, squad_id). Model SquadHeadCount (+
  factory), added to the tenancy guard.
- **Jobs**: UpdateSquadHeadCountJob (insert in a savepoint; on the key, a locking read and overwrite — latest wins);
  GetSessionSquadJob (a Squad with a child in now, else 404); GetSessionHeadCountsJob (by Squad id, one query).
  Two-connection tests: on the Job, and on the whole HTTP request (`atTheSameTime` forking two `put`s) — one row, two
  302s, no 500. Revert-checked: a naive check-then-insert Job fails the Job test with a duplicate key.
- **Feature / route**: `PUT /{church}/classes/{schedule}/squads/{squad}/head-count` `{count}` (ConfirmHeadCountFeature,
  `church.door.head-count`; the door's people; today's class with a Session, a Squad with a child in, 0–200 whole).
  Quiet on success (a quiet change in the matrix: proof = the row has the count sent). HeadCountInput;
  SquadHeadCountData `{count, counted_at, counted_by}` on WhosInSquadData `head_count` (null = not yet). Stories
  #121–#123.
- **Grid/allowlists**: 1 row × 15 actors; other-church id guesses now 22; Who's in allowlist adds `head_count.*`
  (AccessWorld now has a head count for Amara's Squad).
- **Page**: HeadCountForm (molecule, TextField `inputmode=numeric`) under each Squad's list in WhosInSquadCard; the
  confirm goes through useRollCall's queue (after any taps; refresh skipped while it waits; dropped on leaving),
  errors kept by Squad (`errorBag` `head-count-{id}`).
- **Look step fix**: on a phone the label and button text wrapped and a refused count's message squeezed into a
  narrow column; now stacked full width on a phone, one row from sm up. The message was shortened to "Please type a
  number from 0 to 200."
- **For Roy**: the warning's "Not marked" lists every unmarked child in the Squad — on a busy Squad that can be long.
  The confirmed line shows the first word of the staff member's name (the e2e Lifeshaper is literally "Lifeshaper").

**2026-10-28 — review fixes (agent).** Eight items:
- **Prefill** (coordinator's call): the field starts at the last confirmed count, else the number checked in; it
  follows the server until the leader types, and goes back to following once their confirm is answered (not after a
  refusal). Vitest.
- **One atomic upsert**: UpdateSquadHeadCountJob is now a single INSERT … ON DUPLICATE KEY UPDATE on (session_id,
  squad_id) — no exception path, no locking read, safe inside a transaction. The two-connection tests (Job and whole
  HTTP request) now also race on a Squad already counted: one row, no 500. A later confirm is the one kept
  (sequential tests).
- **`confirming` reference-counted** in useRollCall (two counts queued for one Squad). Vitest.
- **HeadCountInput** refuses a JSON `true` (Laravel's `integer` would read it as 1). Feature test.
- **SquadHeadCountFactory** makes its Squad at the Session's own satellite (WorldTest); the matrix's proof names the
  Session too.
- **Copy**: "Not marked: Isabel, Mia" (no trailing period), as the spec.
- **The blue streak** at Level 1's head count (look-02/01): not the page. The DOM at that moment held Inertia's
  progress bar (`div.bar`, 2px, fixed to the top of the viewport, mid-animation); the full-page screenshot drew it at
  the scroll offset. Once the request settled it was gone (look-02b/02, 03). Nothing to fix.
Gates: `make check` green (Pest 1774 + arch 173, Vitest 379, PHPStan L10); `make e2e` green (78 passed, 1 skipped).
