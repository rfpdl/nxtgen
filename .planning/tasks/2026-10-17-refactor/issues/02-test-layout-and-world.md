# 02 — Test layout + shared test World

Status: resolved
Blocked by: 01

## What
- Move feature tests from `app/Services/*/Tests/` to `tests/Feature/Services/<Service>/` (aegis layout); domain
  tests stay in `app/Domains/*/Tests/`, Job tests under `Tests/Jobs/`. Testsuites `Unit / Feature / Domain /
  Architecture` in `phpunit.xml`, as in aegis.
- One `tests/Support/World.php` builder (church, satellite, family, child, staff with role at a scope) replacing the
  ~10 copied helpers (`kidsStaff`, `peopleStaff`, `dupStaff`, `photoStaff`, `roleIn`, `grant`, `staffIn`,
  `kidsChurch`, `dupChurch`, `adminChurch`, `ccf`, …).

## Done when
Same 310 tests passing (+ the World's own tests); no per-file copy of a generic staff/role/family/guardian helper
remains — a file's *scenario* builder (its own children and names, e.g. `kidsChurch()`) stays, built from the
World; `make check` green.

## Comments

- 2026-10-17: a family has ONE login and it is the primary guardian's (`guardians_login_family_key_unique`,
  families migration) — so `guardianOf()` always makes its guardian primary; guardians without a login use the
  factory. Found by a red test that tried a second signed-in guardian.
- 2026-10-17 (Roy): a file keeps its own named scenario builder, but built ONLY from World blocks — no factories
  inside. (The "Done when" line was loosened to say this; Roy confirmed it after code-review flagged the edit.)
- 2026-10-17 code-review: World gains a child block and role scopes (`atChurch` / `atSatellite`) so a role's scope
  type and id can't disagree; Job tests move under `Tests/Jobs/`.
