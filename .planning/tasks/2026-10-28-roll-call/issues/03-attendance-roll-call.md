# 03 — Attendance shows the roll call

Status: ready-for-agent
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
present + head count. E2E: extend the Attendance journey.
