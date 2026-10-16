# 02 — Test layout + shared test World

Status: ready-for-agent
Blocked by: 01

## What
- Move feature tests from `app/Services/*/Tests/` to `tests/Feature/Services/<Service>/` (aegis layout); domain
  tests stay in `app/Domains/*/Tests/`, Job tests under `Tests/Jobs/`. Testsuites `Unit / Feature / Domain /
  Architecture` in `phpunit.xml`, as in aegis.
- One `tests/Support/World.php` builder (church, satellite, family, child, staff with role at a scope) replacing the
  ~10 copied helpers (`kidsStaff`, `peopleStaff`, `dupStaff`, `photoStaff`, `roleIn`, `grant`, `staffIn`,
  `kidsChurch`, `dupChurch`, `adminChurch`, `ccf`, …).

## Done when
Same test count (310) passing; no per-file church/staff helper functions remain; `make check` green.
