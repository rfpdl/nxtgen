# TODO

Numbers are stable — reviews and code comments refer to them. Grouped by when they are needed.
Updated 2026-10-17. **The refactor (`.planning/tasks/2026-10-17-refactor/`) comes first** — it absorbs #1, #17, #18, #19, #24. Decisions behind these: `docs/DECISIONS.md`; security items: `SECURITY.md`.

## Before Release 1 ships (exit criteria, plan §12)

| # | Item | Why | Owner |
|---|---|---|---|
| 18 | **Access-matrix grid:** every R1 Feature × anonymous, parent-only, staff-only, parent+staff, revoked staff, other-satellite staff, other-church user; and **response allowlists** per actor (e.g. the door search's lite view never carries a photo or health field) | plan §3.2, §3.3, §12 · SECURITY #4, #5 | Claude (~1 day) |
| 21 | **Staging dry run:** a real photo upload / show / hide against the private Laravel Cloud bucket; the registry import with the real sheet, reconciliation balances | review 3 · plan §12 | Claude + Roy |
| 16 | Try a photo on a real iPhone (camera, Photos, Files) — HEIC is converted on the phone (`lib/photoForUpload`) | photo | Roy |

## Roy — to go live

| # | Item | Status |
|---|---|---|
| 23 | Laravel Cloud app + MySQL + **private** bucket (`PHOTOS_DISK=s3`) + domain; a mail provider key + sending domain; the Registry tab exported as CSV and the review file checked (with Ate Noemi). First admin = blivejesus@gmail.com (in config) | Roy handling |
| 3 | (same as #23 — Laravel Cloud, D2) | merged into #23 |

## Release 2 — Check-in (28 Oct)

| # | Item | Why |
|---|---|---|
| 9 | Class-admin roles (event series) in People — `canGrant` refuses them until series exist | People |
| 12 | E2E per breakpoint: a **tablet** project (iPad portrait/landscape) and layout assertions per size (no sideways scroll, ≥44/56px targets, menu fits); also run the staff photo-consent journey on phones (needs its own child per run) | Roy 2026-10-15 · review 4 |
| 13 | E2E for link EXPIRY (2 h login / 7 d verify) — needs a controllable clock in the e2e server; Pest covers it | review 2 |
| 7, 20 | **Two-connection concurrency tests:** claim acceptance; photo upload vs consent withdrawal; last-admin removal | review-spar · review 3 |
| 19 | TenancyArchTest blind to `DB::table()` / `->from()` / joins on tenant tables; ReachabilityTest matches basenames, not FQCNs — check-in adds builder queries | review 3 (B4) |
| 17 | Missing arch guards from plan §10: DataObject, Enum, Migration, MorphMap, PhpStyle, ClassLength, TestCoverage (before the R2 freeze) | review 3 (B4) |
| 10 | E2E "test map" like admin-v2 (`e2e/map/serve.mjs`): the access matrix as a page, each cell → its tests, with Run buttons | Roy: admin-v2 has a good page per item |
| — | Open questions for R2: `lifeshapers_can_open` default; follow-up threshold (weeks absent) — plan §13 | plan §13 |

## Later

| # | Item | Trigger |
|---|---|---|
| 1 | ~~Moving-Up for a mixed group~~ — goes away with Levels + Squads (refactor 05) | superseded 2026-10-17 |
| 8 | A church slug must never be a reserved path — ChurchSeeder refuses one; enforce again in any create-church screen | with Platform screens |
| 22 | Photo housekeeping: a scheduled sweep deleting stored photo files no `children.photo_path` points to (never shown — nothing points at them) | after R1 |
| 24 | Lists are paged in memory (50 per page) — move paging into the query | if a church reaches thousands |
| 25 | A "delete this photo for good" action (a parent asking for erasure) — withdrawal only hides (SECURITY #19) | when a parent asks, or before a data-protection review |
| — | "No group" filter on the children list (plan §13 q5) — now "no Level" | awaiting Roy |
| 26 | Storybook, as aegis (a story per component) — not now (DECISIONS 2026-10-17) | when components grow |

## Done

| # | Item | When |
|---|---|---|
| 2, 11 | E2E harness and journeys for everything built — 43 runs on iPhone Safari, Android Chrome, desktop Chrome | 2026-10-15/16 |
| 4 | D-R1: import once, app is the source of truth (plan §7.6) | 2026-10-15 |
| 5 | D-R2: People screens + `ChurchSeeder`; one satellite "CCF Dxb"; first admin blivejesus@gmail.com | 2026-10-16 |
| 6 | Remaining R1 build list (review 1) — all built | 2026-10-16 |
| 14 | Sentry wired with a PII scrubber — kept OFF for now (Roy) | 2026-10-16 |
| 15 | Photos + consent (parent or Lifeshaper; consent wording approved by Roy) | 2026-10-16 |
