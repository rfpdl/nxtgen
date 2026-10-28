# 03 — Attendance shows the roll call

Status: resolved
Blocked by: 02

## What
- Attendance screen: each child shows **Present ✓** or **Not marked**; each Squad heading shows its confirmed head
  count ("Head count 4 · 13:20 by Joy") or "No head count", and the warning when it doesn't match (as on Who's in).
- Excel: two new columns after "Checked in by" — **Present** ("Yes" or empty) and **Squad head count** (the number or
  empty, repeated on each row of that Squad). Still plain text, no health detail.

## Done when (what you see)
An admin opens Attendance for today's 1PM and sees Amara "Present ✓", Isabel "Not marked", and "Head count 2" with
the warning on Level 2 Girls; the Excel has the Present and Squad head count columns.

## Test seams
Feature: Attendance + export carry the new fields (allowlist), Excel columns. Jobs: GetSessionAttendanceJob returns
each child's present mark; the head counts come from GetSessionHeadCountsJob (with their Squads). E2E: extend the
Attendance journey.

## Comments

**2026-10-28 — built (agent), not committed.** `make check` green (Pest 1776 + arch 173, Vitest 387, PHPStan L10, build,
migrate-check) and `make e2e` green (79 passed, 1 skipped as before). Looked at on a 390px phone and at 1280 as a church
admin, after ticks and a count one short: fine as built.

- **Server**: AttendanceChildData gains `present`; AttendanceSquadData gains `head_count` (SquadHeadCountData, null =
  not counted) from GetSessionHeadCountsJob in BaseAttendanceFeature. Only children still in are listed
  (GetSessionAttendanceJob; a Job test proves an undone child marked present never shows). Stories #124 (screen),
  #125 (Excel).
- **Excel**: "Present" ("Yes" or empty) and "Squad head count" (or empty, on each row of its Squad) after "Checked in
  by"; still TextRow, no health detail. Every expected header updated (Job, Feature, allowlist, e2e).
- **Screen**: AttendanceSquadTable — under the heading "Head count 4 · 13:20 by Joy" or "No head count" and the
  warning; a "Roll call" column (sm+) and the last line of each phone row: "Present ✓" (a success Pill) or "Not
  marked". The warning rule is shared with Who's in: `headCountWarning` in `resources/js/lib/rollCall.ts` (Vitest).
- **Allowlist**: Attendance adds `children.*.present` and `head_count.*`.
- **E2E**: a new Attendance journey (desktop) — the admin checks in their own Reyes girl (Tala, a third roll-call girl
  per browser in E2ESeeder / world.ts / E2EWorldTest), ticks her on Who's in, counts Level 2 Girls one too many, and
  sees on Attendance "Present ✓", the head count and the warning; the Excel row ends "Yes|<count>". The first
  Attendance journey now builds its expected Excel rows with the two new columns.
- **Found by the e2e and fixed** (HeadCountForm, issue 02's prefill): the field went back to following the server when
  the confirm was ANSWERED, so a number typed straight after the confirmed line appeared was overwritten — the second
  confirm sent the old number. It now follows from the moment a count is sent; typing afterwards keeps the new number,
  and a refusal keeps what was typed (Vitest).

**2026-10-28 — review fixes (agent).** Seven items:
- **A counted Squad whose children were all undone since** no longer vanishes: AttendanceSquadData merges in each
  Squad with only a head count — "0 in", its head count line, "Checked in 0, counted 2." — in the Attendance order
  (one rule, `Squad::rank()`, now also behind GetSessionAttendanceJob's order). The Excel has one row for it: the
  Squad, empty child cells, the head count. GetSessionHeadCountsJob loads each count's Squad. Tests: DTO
  (`tests/Feature/DataObjects/AttendanceSquadDataTest.php`), Job (Excel), Feature (page + Excel), Vitest (no rows).
  Who's in stays live-only.
- **Refused-count race** (HeadCountForm): the number sent is kept and put back on a refusal, even when the server sent
  another number meanwhile (Vitest, in that order).
- **One label**: `headCountLabel` in `resources/js/lib/rollCall.ts` ("13:20 by Joy") behind both "✓ Confirmed 13:20 by
  Joy (4)" and "Head count 4 · 13:20 by Joy".
- **One roll-call cell**: the RollCallMark atom ("Present ✓" / "Not marked") for the phone rows and the table.
- **Fixture**: a head count on another Session (1PM two weeks before) is left out of the page.
- **E2E**: the Attendance journey also checks in Faith (a fourth Reyes girl per browser) and leaves her unmarked:
  "Not marked" on her row, and the warning names her.
- Test seams line corrected; AttendanceChildData's docblock wrapped.
Gates: `make check` green (Pest 1779 + arch 173, Vitest 392, PHPStan L10); `make e2e` green (79 passed, 1 skipped).

