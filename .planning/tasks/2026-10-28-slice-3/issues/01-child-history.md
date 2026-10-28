# 01 — Child history on the staff child page

Status: resolved

## What
- On the staff page where a child's details show (the family page / child card staff open — find the existing one),
  an "Attendance" section per child: newest first, each line date · class · satellite · Squad · Present ✓ / Not
  marked. Undone Check-ins never show. Show the last 12 by default with "Show all" (or paging) if more.
- Who sees it: staff who may open that family (same rule as the page). Parents do NOT see it (My family unchanged).
- Empty: "Not checked in yet."

## Done when (what you see)
A Lifeshaper opens the Reyes family and, under Amara, sees "Sat 31 Oct · 1PM · CCF DXB · Level 2 Girls · Present ✓"
and an older line "Not marked"; a parent signed in to My family sees no attendance.

## Test seams
Job: a child's Check-ins (not undone), newest first, with present. Feature: who may see (grid unchanged — same page),
allowlist update, parent page doesn't carry it. E2E: check in at the door → the family page shows the line.

## Comments

**2026-10-28 — built (agent), not committed.** `make check` green (Pest 1824 + arch 173, Vitest 396, PHPStan L10, build,
migrate-check) and `make e2e` green (85 passed, 1 skipped as before). Looked at on a 390px phone and at 1280; one small
fix (the block now has a line under it, so it does not run into the photo controls).

- **Job**: GetFamilyCheckInsJob — the family's children's Check-ins, not undone, newest Session first (date, then the
  later class), with class, satellite, Squad, child; constant queries however many (feature test).
- **Data**: ChildAttendanceData `{child_id, check_ins}` per child (family order), ChildCheckInData `{id, date, class,
  satellite, squad, present}` — date "Sat 31 Oct", with the year when not this year (church's). Sent as `attendance`
  on Church/Families/Show only (ShowFamilyFeature; access row unchanged). Stories #126 (staff see it) and #127 (a
  parent's page never carries it).
- **Screen**: ChildAttendanceList (molecule) at the top of each child's card on the staff family page: lines with
  RollCallMark, the last 12 then "Show all N"; "Not checked in yet." when empty.
- **Tests**: Job; Feature (lines + order + undone left out + year; query count; parent page has no attendance);
  allowlist (`attendance.*`); Vitest; e2e `tests/e2e/parent/history.spec.ts` (all three browsers): a parent registers
  Amara, a Lifeshaper checks her in and ticks her present, the family page shows "… · 1PM · CCF DXB · Level 2 Girls"
  + "Present ✓"; the parent's My family shows no attendance.
- **Not in e2e**: the older "Not marked" line of the Done-when (no way to make a past Session from the screens) — the
  feature test covers an older line.

**2026-10-28 — review fixes (agent).** Job test: a Check-in of the child carrying another church is left out
(revert-checked: drops the church line → fails). Vitest: the first line is the newest and the 12th the 12th-newest;
exactly 12 shows all, no "Show all". Feature: "this year" in the church's time (1 Jan 01:00 Dubai, still 2026 in UTC →
"Sat 31 Oct 2026"). FamilyPageTest asserts `missing('attendance')` and no child carries `check_ins`. Dropped the unused
`child` load (job) and the no-op sort (feature). Data tests in `tests/Feature/DataObjects/ChildAttendanceDataTest.php`
(forChildren, year branch). ChildCheckIn alias documented and used. The e2e moved to `tests/e2e/staff/history.spec.ts`
(phones too, testMatch updated) and now undoes the check-in at the door: the line is gone. DECISIONS notes that past
lines show today's names and the lazy "Show all" option.
Gates: `make check` green (Pest 1829 + arch 173, Vitest 397, PHPStan L10); `make e2e` green (85 passed, 1 skipped).
