# 05 — Levels, Squads, Schedules

Status: ready-for-agent
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
