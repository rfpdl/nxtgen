# 01 — Classes: admins create and edit a satellite's classes

Status: resolved
Blocked by: —

## What
- `schedules` table (church_id, satellite_id, name, weekday nullable, starts_at/ends_at nullable times, is_active).
- A "Classes" page for admins (church admin: every satellite; satellite admin: their own): list, add, edit, retire.
- Seed CCF Dxb with "1PM" (Saturday 13:00–?) and "7PM" (Saturday 19:00–?) in the demo/church seeder.

## Done when (what you see)
An admin opens **Classes**, sees CCF Dxb's 1PM and 7PM, adds "Special class" with no day, edits a time; a Lifeshaper
doesn't see the Classes menu; a satellite admin sees only their satellite's classes.

## Comments

**2026-10-26 — built (agent), not committed.** `make check` and `make e2e` green (Pest 1459 + arch 173; e2e 53).

- **Schema**: new migration `2026_10_26_000100_create_schedules_table` — church_id, satellite_id, name, weekday
  (nullable `WeekdayEnum`, stored `monday`…`sunday`, `isoNumber()` for ISO order), starts_at/ends_at (nullable
  `time`), is_active. "Unique name among active" is a generated `active_name` column (`IF(is_active, name, NULL)`)
  with unique (satellite_id, active_name) — MySQL NULLs are distinct, so retired rows never collide.
- **Code**: `Schedule` model + factory (tenancy guard list), `App\Domains\Schedule` (GetSatelliteSchedulesJob,
  GetScheduleJob, CreateScheduleJob, UpdateScheduleJob, UpdateScheduleAsInactiveJob; ScheduleData,
  SatelliteSchedulesData). `AccessScope::canManageClasses()` / `canManageClassesAt(Satellite)`, access row
  `ManageClasses`, `church_nav.can_manage_classes`.
- **Routes** (address says "classes", code says Schedule): `GET /{church}/classes` (church.schedules.index),
  `POST /classes` (store), `PUT /classes/{schedule}` (update), `PUT /classes/{schedule}/retire` (retire). Page
  `Church/Schedules/Index`: one section per satellite, edit in place, "Add a class at …", Retire asks once more.
- **Stories** 95–102 (show, add, edit, retire; happy + unhappy). Access matrix, response allowlist and wire-casing
  cover the new routes; AccessWorld has a class at Home and Away.
- **Seed**: DemoChurchSeeder (so local + e2e) gives CCF DXB "1PM" Sat 13:00–15:00 and "7PM" Sat 19:00–21:00;
  ChurchSeeder (production) makes no classes.
- **For Roy**: end times picked as 15:00 and 21:00 (the issue said "?"); e2e retires the "Special class" it adds,
  not 1PM/7PM, so later journeys keep them. Unrelated: `GetChildrenJobTest` "searches the child's own names…" is
  flaky — a faker middle name like "Tamara" matches the search "amara" (failed once in a parallel run).


**2026-10-26 — review fixes (agent).** The Classes page now makes two queries: `AccessScope::classSatelliteIds()`
filters the satellites, and `GetSchedulesBySatelliteJob` (replacing GetSatelliteSchedulesJob) loads every class,
keyed by satellite. Removed the unused `Satellite::schedules()`. `ends_at` is a plain `after:starts_at`, which passes
when there is no start. The day picker says "Saturday" (`label()`) and the class line says "Saturdays"
(`everyLabel()`). Each satellite has a "+ Add a class" button that opens the form, which closes on save or cancel.
Pinned the guardian emails in GetChildrenJobTest and GetFamiliesJobTest's search tests. Both gates are green again.
