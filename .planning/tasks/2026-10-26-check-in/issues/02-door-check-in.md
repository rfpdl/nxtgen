# 02 — Check in at the door

Status: resolved
Blocked by: 01

## What
- `sessions` (church_id, satellite_id, schedule_id, date; unique schedule+date) created by the first Check-in.
- `check_ins` (session_id, child_id, squad_id, checked_in_at/by, undone_at/by; unique session+child; a re-check-in
  after Undo revives the row). Concurrency-safe (two phones at once → one Check-in).
- The child → Squad lookup (this Satellite, the child's Level and gender) deferred from refactor 05.
- **Door page** (phone first): today's date; class picker (the satellites the viewer's Role covers; a class with a
  weekday only on that day); count checked in; the existing church-wide search; family cards with children:
  tick + "Check in N children"; ✓ in + Undo; inactive → "Make active again?" then in; Graduated → refused with the
  reason; no Level → pick one (age suggestion pre-selected, saved, noted on history); ⚠ allergy flag; tap a name for
  health details. No photo, no phone number.
- Access: anyone with a Role at that satellite (church-wide roles: any satellite).

## Done when (what you see)
On an iPhone, a Lifeshaper picks **1PM**, searches "Cruz", ticks two children (one inactive), confirms, sees both
✓ and the count go up, undoes one; a child with no Level asks for a Level first; a Graduated child can't be ticked;
a visiting House Church child can be checked in at CCF Dxb.

## Comments

**2026-10-26 — built (agent), not committed.** `make check` green (Pest 1589 + arch 173, Vitest 163, PHPStan L10) and
`make e2e` green (68 runs; the door's 5 journeys × iPhone Safari, Android Chrome, desktop Chrome). Looked at on an
iPhone viewport: fixed a sideways scroll (search box) and a small native class picker on iOS.

- **Schema** (`2026_10_26_000200_create_check_in_tables`): `schedule_sessions` (model `Session` — Laravel's session
  driver owns `sessions`), unique (schedule_id, date); `check_ins`, unique (session_id, child_id), `squad_id` required,
  checked_in_at/by, undone_at/by — Undo stamps the row, a new check-in revives it.
- **Jobs**: CheckIn domain — CreateSessionJob, GetSessionJob, CreateCheckInJob (createOrFirst + conditional revive),
  UpdateCheckInAsUndoneJob, GetCheckInJob, GetCheckedInChildrenJob; GetChildSquadJob (Level; this satellite's Squad,
  Level 1 mixed, made from the chart if missing); GetTodaysSchedulesJob; GetChurchTodayJob (church timezone);
  GetChurchChildrenJob. `CheckInResultEnum`. `CheckInChildOperation` (Graduated / inactive / no Level, then status,
  Level + history notes, Session, Squad, Check-in in one transaction).
- **Features / routes** (access row `CheckIn`, `AccessScope::canCheckInAt`/`doorSatelliteIds`): `GET /{church}/door`
  (ShowDoorFeature), `POST /{church}/classes/{class}/check-ins` (CheckInChildrenFeature, result per child — success and
  "Not checked in: …" flashes), `PUT /{church}/check-ins/{id}/undo` (UndoCheckInFeature, today only). Health details
  via `?health=` partial reload only. Stories #103–#111.
- **Page** `Church/Door/Index` + DoorChildRow, ChildHealthPanel, DoorFamilyCard; "Check in" first in the staff menu.
- **Concurrency helper** `tests/Support/Concurrency.php`: `withCommittedWorld` + `atTheSameTime` (forked processes,
  one MySQL connection each). Proves one Check-in and one Session when two phones press at once; revert-checked
  against a naive check-then-insert (it fails with a duplicate-key error).
- **Grid/allowlists**: 3 door rows × 15 actors; other-church id guesses now 20; door + health-tap allowlists;
  WorldTest blocks `sessionOf`, `checkedIn`. Tenancy guard covers Session and CheckIn (proven by removal).
- **E2E**: per-browser Cruz (DXB) and Villa (House Church) families in E2ESeeder; the e2e server's door day is the next
  Saturday (`nxtgen.door_today`, set only by E2EServiceProvider).
- **For Roy**: DECISIONS 2026-10-26 (later) — table name, undo today only, door never changes an existing Level;
  SECURITY #21 — health tap shows any found child's details (a visitor too): keep or narrow?

**2026-10-26 — review fixes (agent).** A race the Job-level tests missed: inside the Operation's transaction (REPEATABLE
READ) `createOrFirst`'s fallback read could not see the other phone's row, so the second phone got a 500. Two new
`atTheSameTime` tests on the whole `CheckInChildOperation` (Session already made / not yet, Squad missing) failed on
it; now the Jobs insert inside a savepoint and, on the duplicate key, read with a LOCKING read (a plain pre-read for the
Session — a locking read of a missing row deadlocked). Both phones get "checked in" / "already checked in".
`GetChildSquadJob` now only reads (null when missing); `CreateChildSquadJob` makes it. `said()` simplified. CI
installs `pcntl`/`posix`. The e2e door day uses the church timezone from config. Child row laid out as box | name +
Squad, then flags/status underneath (no stray "·"); Level and class pickers 56px on iOS; every e2e card asserts nothing
pokes out. SECURITY #21 restated (kept as built). Gates: Pest 1596 + arch 173, Vitest 164, e2e 68.
