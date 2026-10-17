# TODO

Numbers are stable — reviews and code comments refer to them. Grouped by when they are needed.
Updated 2026-10-17. **The refactor (`.planning/tasks/2026-10-17-refactor/`) comes first** — it absorbed #1, #17, #18, #19, #24 (all done). Decisions behind these: `docs/DECISIONS.md`; security items: `SECURITY.md`.

## Before Release 1 ships (exit criteria, plan §12)

| # | Item | Why | Owner |
|---|---|---|---|
| 21 | **Staging dry run:** a real photo upload / show / hide against the private Laravel Cloud bucket; the registry import with the real sheet, reconciliation balances | review 3 · plan §12 | Claude + Roy |
| 28 | ~~Resend rule~~ — Roy: anyone who can open the family (issue 11) | decided 2026-10-17 | done |
| 16 | Try a photo on a real iPhone (camera, Photos, Files) — HEIC is converted on the phone (`lib/photoForUpload`) | photo | Roy |

## Roy — to go live

| # | Item | Status |
|---|---|---|
| 23 | Laravel Cloud app + MySQL + **private** bucket (`PHOTOS_DISK=s3`) + domain; a mail provider key + sending domain; the Registry tab exported as CSV and the review file checked (with Ate Noemi). First admin = blivejesus@gmail.com (in config) | Roy handling |
| 3 | (same as #23 — Laravel Cloud, D2) | merged into #23 |

## Release 2 — Check-in (28 Oct)

| # | Item | Why |
|---|---|---|
| 9 | Schedule-admin roles (shown as "class", `ScopeTypeEnum::Schedule`) in People — `canGrant` refuses them until Schedules exist | People |
| 12 | E2E per breakpoint: a **tablet** project (iPad portrait/landscape) and layout assertions per size (no sideways scroll, ≥44/56px targets, menu fits); also run the staff photo-consent journey on phones (needs its own child per run) | Roy 2026-10-15 · review 4 |
| 13 | E2E for link EXPIRY (2 h login / 7 d verify) — needs a controllable clock in the e2e server; Pest covers it | review 2 |
| 7, 20 | **Two-connection concurrency tests:** claim acceptance; photo upload vs consent withdrawal; last-admin removal | review-spar · review 3 |
| 10 | E2E "test map" like admin-v2 (`e2e/map/serve.mjs`): the access matrix as a page, each cell → its tests, with Run buttons | Roy: admin-v2 has a good page per item |
| — | Open questions for R2: `lifeshapers_can_open` default; follow-up threshold (weeks absent) — plan §13 | plan §13 |

## Later

| # | Item | Trigger |
|---|---|---|
| 27 | **Moving-Up** (admin-run, never automatic): each Child's Level + 1; from the top Level (4) the Child's status becomes Graduated. Nothing is built yet beyond `children.level` (refactor 05) | before the February Moving-Up |
| 8 | A church slug must never be a reserved path — ChurchSeeder refuses one; enforce again in any create-church screen | with Platform screens |
| 22 | Photo housekeeping: a scheduled sweep deleting stored photo files no `children.photo_path` points to (never shown — nothing points at them) | after R1 |
| 25 | A "delete this photo for good" action (a parent asking for erasure) — withdrawal only hides (SECURITY #19) | when a parent asks, or before a data-protection review |
| — | "No Level" filter on the children lists (plan §13 q5; was "no group") — the list shows "No Level" today, there is no filter for it | awaiting Roy |
| 26 | Storybook, as aegis (a story per component) — not now (DECISIONS 2026-10-17) | when components grow |

## Later — from Roy's first look (2026-10-17)

| # | Item | Trigger |
|---|---|---|
| 29 | **Merge two families or two children** (a child registered again, legacy duplicates) — write a spec first (who may, what moves, history) | when a re-registration happens |
| 30 | **UI overhaul** — a design pass over every screen (Roy) | after Release 1 polish |

## Done

| # | Item | When |
|---|---|---|
| 2, 11 | E2E harness and journeys for everything built — 43 runs on iPhone Safari, Android Chrome, desktop Chrome | 2026-10-15/16 |
| 4 | D-R1: import once, app is the source of truth (plan §7.6) | 2026-10-15 |
| 5 | D-R2: People screens + `ChurchSeeder`; one satellite "CCF Dxb"; first admin blivejesus@gmail.com | 2026-10-16 |
| 6 | Remaining R1 build list (review 1) — all built | 2026-10-16 |
| 14 | Sentry wired with a PII scrubber — kept OFF for now (Roy) | 2026-10-16 |
| 15 | Photos + consent (parent or Lifeshaper; consent wording approved by Roy) | 2026-10-16 |
| 17 | Arch guards from aegis: PhpStyle, DataObject, Enum, Migration, ClassLength, TestCoverage, Job constructors, Operation + Middleware boundaries, base classes, domain test location, MorphMap (adapted to `enforceMorphMap`) — each with a shrinking known-gaps list (`tests/Architecture/KnownGaps.php`). Refactor batch 03 | 2026-10-17 |
| 1 | ~~Moving-Up for a mixed group~~ — superseded: groups replaced by Levels + Squads (DECISIONS 2026-10-17). Refactor batch 05 | 2026-10-17 |
| 18 | Access-matrix grid: every staff/parent route (30, from the router) × 15 actors (anonymous, two parents, church/satellite admin, church/satellite Lifeshaper, two both-hats, three revoked kinds, other-satellite admin + Lifeshaper, other-church admin) = 450 cells, plus another church's admin trying all 16 id-carrying routes from their own address — `tests/Feature/AccessMatrixTest.php`; the 16 Auth/Public routes × 4 actors — `OpenRouteAccessTest.php`; exact response allowlists per actor + the Excel header — `ResponseAllowlistTest.php`. No access bug found. Refactor batch 09 | 2026-10-17 |
| 24 | Lists paged in the database (count + limit/offset, eager loads per page) — `ListPage::of(Builder)`; Excel still every filtered row. Refactor batch 09 | 2026-10-17 |
| 19 | TenancyArchTest sees `DB::table()` / `->from()` / joins / static finds; ReachabilityTest matches FQCNs. Refactor batch 03 | 2026-10-17 |
