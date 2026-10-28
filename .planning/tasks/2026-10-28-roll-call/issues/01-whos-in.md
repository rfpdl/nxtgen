# 01 — Who's in by Squad + tap present

Status: resolved
Blocked by: —

## What
- **A second tab on the door page: "Who's in"** (`GET /{church}/door/whos-in`, its own Feature + page
  `Church/Door/WhosIn`). Both pages show the same tab bar (Check in · Who's in) and keep the class picked when
  switching (`?class=`); same class picker rules, same date (the door's today), same access as the door
  (`AccessScope::canCheckInAt` / `doorSatelliteIds`).
- Shows today's Session for that class: children checked in (not undone), grouped by Squad in the Attendance order
  (Level, then mixed, boys, girls; children by nickname). Each Squad heading: its Level pill + "N of M present"
  (present / checked in). Allergy flag on a child as at the door. No photo, no phone.
- **Squad chips** (ChoiceChips): "All" + each Squad with a Check-in today; `?squad=` keeps the choice. No Session
  yet → a friendly empty state ("No one checked in yet") with a button to the Check in tab.
- **Tap present**: tapping a child's row toggles present (CheckRow-style, the whole row, ≥48px). `PUT
  /{church}/check-ins/{checkIn}/present` with `{present: bool}` — today's Session only, not an undone Check-in,
  same access as Undo. Records `present_at` + `present_by` on `check_ins`; unmarking clears both (latest wins, no
  history). Undo clears the mark; a re-check-in after Undo starts unmarked.
- The page refreshes its list every ~20 s while open (Inertia poll), so children checked in at the door appear.
- Door page: the existing checked-in list stays; only the tab bar is added.

## Done when (what you see)
On an iPhone, a Lifeshaper on **1PM** taps **Who's in**, picks **Level 2 Girls**, taps Amara and Sofia: both show ✓
and the heading reads "2 of 3 present"; tapping Amara again unticks her. Back on **Check in** the class is still 1PM.
A child undone at the door disappears from Who's in.

## Test seams
Feature: page + toggle — access grid rows (15 actors), allowlist, today only, undone refused, other church 404.
Jobs: mark/unmark, Undo clears, re-check-in unmarked. E2E: the Done-when journey on the three projects.

## Comments

**2026-10-28 — built (agent), not committed.** `make check` green (Pest 1733 + arch 173, Vitest 349, PHPStan L10, build,
migrate-check) and `make e2e` green (78 passed, 1 skipped as before; the Who's in journey × iPhone Safari, Android
Chrome, desktop Chrome). Looked at on a 390px phone and at 1280: fine, after one fix (below).

- **Schema** (`2026_10_28_000100_add_present_to_check_ins`): `present_at`, `present_by` (FK users, null on delete),
  nullable. Undo clears both (UpdateCheckInAsUndoneJob); the revive in CreateCheckInJob resets them.
- **Jobs**: UpdateCheckInPresentJob (renamed UpdateCheckInPresenceJob in the review fixes; mark / unmark, latest wins); GetChosenScheduleJob (the `?class=` rule, now shared
  by the door and Who's in — ShowDoorFeature's private `chosen()` moved there); Who's in reuses GetSessionAttendanceJob
  for the Attendance order. `CheckIn::isPresent()`, `Child::hasAllergies()` (DoorChildData uses it too).
- **Features / routes** (access row `CheckIn`): `GET /{church}/door/whos-in` (ShowWhosInFeature, `church.door.whos-in`,
  same people as the door), `PUT /{church}/check-ins/{checkIn}/present` `{present}` (MarkPresentFeature,
  `church.door.present`, same as Undo: today only, a Role covering the satellite; undone → error flash). DTOs
  WhosInSquadData `{id, name, level, present, count, children}`, WhosInChildData `{id, nickname, has_allergies,
  present}`; MarkPresentInput. Stories #117–#120.
- **Page** `Church/Door/WhosIn` + DoorTabs (on both door pages, keeps `?class=`) and WhosInSquadCard (whole-row
  CheckRow, 64px). Polls `squads` every 20 s. Taps show at once and go to the server one after another.
- **Grid/allowlists**: 2 rows × 15 actors; other-church id guesses now 21; Who's in allowlist (nickname, allergy
  yes/no, present — no photo, phone or health detail). The matrix learned "quiet changes" (`quietChanges()`): a change
  with no success message is `ok` only when its proof holds (the Check-in is marked present).
- **E2E**: `tests/e2e/staff/whos-in.spec.ts` (added to the phone projects). The three browsers share today's 1PM
  Level 2 Girls, and every Cruz family has an "Amara", so each browser has its own Reyes girls (E2ESeeder
  `ROLL_CALL_GIRLS`, world.ts `ROLL_CALL`, E2EWorldTest) and the journey reads "N of M present" before and after.
- **Look step fix**: the first tap's green "Amara is present." band pushed the whole list down 64px — the next tap
  would land on the wrong child. Marking now flashes nothing (DECISIONS 2026-10-28, Claude low-stakes).
- **For Roy**: the Squad chips scroll sideways on a phone, so with many Squads the picked one can sit half off-screen
  (as the class chips do). Two children with the same nickname in one Squad look the same on Who's in (no full name
  sent) — say if you want the full name under the nickname.

**2026-10-28 — review fixes (agent).** Two-axis review, nine items, all fixed:
- **Poll vs tick**: the refresh is skipped while a tap waits, and one already on its way is cancelled when a tap starts
  (it read the list before the tap). **Queue outlives the page**: taps not yet sent are dropped on leaving the page
  and before a chip moves to another class or Squad. Both live in a new composable `useRollCall` (Vitest).
- **Mark vs Undo race**: UpdateCheckInPresenceJob (renamed from …PresentJob) writes `WHERE undone_at IS NULL` and
  returns whether it marked; 0 rows → "Amara is not checked in any more." (Job test for an Undo landing in between).
- **Matrix**: a quiet change's DB proof alone decides `ok` (a success message cannot stand in for it).
- **One fold**: `CheckInGroups::bySquad()` (Foundation/Support) behind both AttendanceSquadData and WhosInSquadData
  (not a static on CheckIn: the tenancy guard reads `CheckIn::` as a query).
- **One class picker**: DoorClassPicker (heading + class chips + no-class note) on both tabs; `DoorQueryInput`
  (`?class=`, `?squad=`) replaces `request()` reads in the two Show features.
- **Page Vitest** (`WhosIn.test.ts`): the 20 s poll, the empty state's "Go to Check in" keeping `?class=`, a Squad chip
  keeping the class.
Gates: `make check` green (Pest 1736 + arch 173, Vitest 362, PHPStan L10); `make e2e` green (78 passed, 1 skipped).
