# 05 — Levels, Squads, Schedules

Status: resolved
Blocked by: 04

The one batch allowed to change screens (DECISIONS 2026-10-17).

## What
- **Level**: one church-wide list in `config/nxtgen.php` from the age-level chart — Level 1 (4–5, mixed), Level 2
  (6–7), Level 3 (8–9), Level 4 (10–12). The child stores `level`; its age only *suggests* one
  (`GetSuggestedGroupJob` → suggest a Level).
- **Squad**: table per Satellite (Level + gender, null = mixed), created from the config chart per Satellite; no
  editing screen. A child's Squad at a Satellite = that Satellite's Squad for the child's Level and gender.
- Remove `groups`, `children.group_id`, `next_group_id`. Moving-Up = Level + 1; from Level 4 → status Graduated.
- Admin "correct a child's group" → "correct a child's Level" (still noted on the history). Children list filters
  by Level; screens show the Level.
- `ScopeTypeEnum::EventSeries` → `Schedule`. Schedule and Session tables are **not** built here (check-in builds
  them) — only the names are settled.
- TODO #1 closed (no mixed-group problem any more).

## Done when
Glossary words on every screen that showed a group; Pest + e2e updated and green.

## Comments

**2026-10-17 — Claude (batch 05 built; not committed, for review).**

- **Level** is config (`config/nxtgen.php` `levels`: 1 = 4–5 mixed, 2 = 6–7, 3 = 8–9, 4 = 10–12, 2–4 by gender), read
  only through `App\Foundation\Support\LevelChart`. A child stores `children.level` (nullable tinyint) with
  `level_set_at` / `level_set_by`. `GetSuggestedLevelJob` (age on `moving_up_reference_date`, whole years) replaced
  `GetSuggestedGroupJob`; registration, add-child and the import use it. `GetLevelsJob` feeds the filters and the
  admin's picker (`LevelOptionData`).
- **Squads**: `squads` table (church, satellite, level, gender null = mixed; unique satellite+level+gender_key),
  made per Satellite by `ChurchSeeder` and `SatelliteFactory::withSquads()`, both through `Squad::createChartFor()`.
  The child→Squad lookup is NOT built (no caller until check-in) — logged in DECISIONS 2026-10-17 for Roy to confirm.
- **Removed**: `groups`, `Group` model/factory, `app/Domains/Group`, `children.group_id`, `next_group_id`,
  `UpdateChildGroupFeature/Job/Input`, route `PUT /children/{child}/group`, the gender-fit rule.
- **Correct a child's Level**: `PUT /{church}/children/{child}/level` (`church.children.level`),
  `UpdateChildLevelFeature` + `UpdateChildLevelJob` + `UpdateChildLevelInput`; church admins only (404 otherwise);
  a Level off the chart → "Please choose one of the church's Levels."; history "X: Level changed from Level 1 to
  Level 2." (or "from no Level"). `AccessRowEnum::SetChildLevel`, `AccessScope::canSetLevel()`.
- **Screens**: children list and families list filter by `?level=` ("Any Level"); rows say "Level 2" / "No Level";
  family page card says the Level or "No Level yet"; the admin picker lists Level 1–4; Excel column "Level".
- **Import**: the review sheet's last column is "Level"; "Level 2", "Level 2 Boys", "level 2" or "2" → 2; anything
  else → "unknown Level" problem, family not loaded. `$groups` (rows by family key) renamed `rowsByFamily`.
- **Schedule words**: `ScopeTypeEnum::Schedule` ('schedule', label "Class"); `AccessScope::$adminScheduleIds`;
  `families.preferred_event_series_ids` → `preferred_schedule_ids` (unused column, renamed only).
- **Guard fix on the way**: ReachabilityTest missed `new X` without `()` (Pint drops empty brackets), so
  `$this->run(new GetLevelsJob)` read as unused. Regex fixed, with its own test case.
- Gates: `make check` all green (Arch 163, Pest 471, Vitest 107); `make e2e` 43 passed.

**2026-10-17 — Claude, after code review (still uncommitted).**

- ReachabilityTest now reads tokens (`T_NEW` + a name, `Name::class`), so a word in a string or comment, `new class`
  and `new static/self/parent` are not calls; probes for "a string is not a call" and `new GetLevelsJob` with no
  brackets.
- `squads.gender_key` (stored, `COALESCE(gender, 'mixed')`) carries the unique key — MySQL treats NULLs as distinct,
  so the old key let two mixed squads in. `SquadInvariantsTest` proves the database refuses one.
- `squads.name` dropped: `Squad::name()` derives it (`LevelChart::squadName`), so it cannot drift from the chart.
- One path for "is this a Level": `UpdateChildLevelInput::rules()` (`Rule::in(LevelChart::levels())`, same
  message); the Feature's own check is gone. Pages still get their lists from `GetLevelsJob`.
- One squad-creation path: `Squad::createChartFor()`, used by ChurchSeeder and `SatelliteFactory::withSquads()`.
- **Moving-Up is deferred** to when Moving-Up is built (TODO #27, before the February Moving-Up): Level + 1, and from
  the top Level the child becomes Graduated. No code now.
- Import, as at HEAD: a "Level 2 Boys" written for a girl is accepted as Level 2 — the gender word is ignored (her
  Squad follows from her gender).
- Considered and skipped: a `Level` value object instead of `int $level` — not needed yet.
- Note: validation runs before the Feature's access check, so a non-admin sending a Level off the chart gets a
  validation error rather than a 404 (a valid Level still gets 404) — the same order as every other Input here.
