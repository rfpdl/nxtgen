# 03 — Session report + Excel

Status: resolved
Blocked by: 02

## What
- Admins only: a **Attendance** page — pick a satellite, class and date → the children checked in, grouped by
  Squad, with a count per Squad and in total; Excel download of the same rows (allowlisted columns).

## Done when (what you see)
An admin opens **Attendance**, picks CCF Dxb · 1PM · today, sees the children per Squad with counts, and the Excel
has the same rows; a Lifeshaper can't open it.

## Comments

**2026-10-26 — built (agent), not committed.** `make check` green (Pest 1645 + arch 173, Vitest 167, PHPStan L10); `make e2e` green (70 runs).

- **Page** `GET /{church}/attendance` (ShowAttendanceFeature, `Church/Attendance/Index`): satellite, class and date
  pickers (`?satellite=&class=&date=`); opens on today's class with a Session, else the latest Session at the first
  satellite. Children in (undone left out) by Squad — Level order, boys before girls — each with nickname, full
  name, Level (the Squad's), time (church timezone) and who checked them in; count per Squad, total, "No one was
  checked in." Menu item "Attendance" only for admins (`church_nav.can_see_attendance`).
- **Excel** `GET /{church}/attendance/export` (ExportAttendanceFeature → CreateAttendanceSpreadsheetJob, TextRow):
  `Squad|Child nickname|Child full name|Level|Checked in at|Checked in by`, named
  `nxtgen-attendance-<satellite>-<class>-<date>.xlsx`.
- **Shared choosing** in abstract `BaseAttendanceFeature`; Jobs `GetSessionAttendanceJob`, `GetDefaultSessionJob`;
  DTOs `AttendanceSquadData` / `AttendanceChildData`; `CheckIn::checkedInBy()`; access row `Attendance`;
  `AccessScope::canSeeAttendance` / `attendanceSatelliteIds`. Stories #112–#115.
- **Tests**: ChurchAttendanceTest (9), access-matrix rows for both routes, allowlists for the page and the Excel
  header, Job tests, AccessScope, AccessRowEnum, ChurchShell, AttendanceSquadTable; e2e
  `tests/e2e/staff/attendance.spec.ts` (desktop: checks Ben in at the door within the journey, picks CCF DXB · 1PM ·
  door Saturday, compares page rows to the downloaded Excel via `tests/e2e/support/xlsx.ts`; a Lifeshaper has no
  Attendance).
- **Decided (DECISIONS, for Roy)**: only active classes are pickable (a retired class's past Sessions wait for slice
  3); "Attendance" added to GLOSSARY as the report's name.
- Not yet looked at in a browser by a person — open `/ccf/attendance` before commit.

**2026-10-26 — review fixes (agent), not committed.** `make check` green (Pest 1660 + arch 173, Vitest 168, PHPStan
L10); `make e2e` green (70 runs).

1. Retired classes that met stay in the picker as "<name> (retired)" and `?class=` works; a retired class with no
   Session is a 404 (GetReportableSchedulesJob; DECISIONS).
2. GetDefaultSessionJob folded into GetAttendanceSelectionJob, so the tautology is gone; its test checks the picked
   (satellite, class, date).
3. `Child::fullName()` is used by Attendance, both spreadsheets, the children list and the door.
   CreateAttendanceSpreadsheetJob moved to the CheckIn domain and writes the page's AttendanceSquadData rows. The
   Excel has no Level column. `Tests\Support\spreadsheetRows` is shared.
4. Picking, defaulting and date parsing live in GetAttendanceSelectionJob (unit-tested); the Features are thin.
5. Squads sort by Level, then mixed, boys, girls (an explicit rank), with a test.
6. `church_nav` is ChurchNavData (#[TypeScript]); SharedProps uses the generated type.
7. No Level column; phones show stacked rows, sm+ shows the table; keys are Check-in ids (Vitest for both).
8. A query-count test: the same count with 3 or 9 children in.
