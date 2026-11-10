# TODO

Numbers are stable — reviews and code comments refer to them. Grouped by when they are needed.
Updated 2026-11-08. **The refactor (`.planning/tasks/2026-10-17-refactor/`) comes first** — it absorbed #1, #17, #18, #19, #24 (all done). Decisions behind these: `docs/DECISIONS.md`; security items: `SECURITY.md`.

## Before Release 1 ships (exit criteria, plan §12)

| # | Item | Why | Owner |
|---|---|---|---|
| 21 | **Staging dry run:** a real photo upload / show / hide against the private Laravel Cloud bucket; the registry import with the real sheet, reconciliation balances | review 3 · plan §12 | Claude + Roy |
| 28 | ~~Resend rule~~ — Roy: anyone who can open the family (issue 11) | decided 2026-10-17 | done |
| 16 | Try a photo on a real iPhone (camera, Photos, Files) — HEIC is converted on the phone (`lib/photoForUpload`) | photo | Roy |

## Roy — to go live

| # | Item | Status |
|---|---|---|
| 23 | Laravel Cloud app + MySQL + **private** bucket (`PHOTOS_DISK=s3`) + domain + **the scheduler switched on** (photo sweep, #22); a mail provider key + sending domain; the Registry tab exported as CSV and the review file checked (with Ate Noemi). First admin = blivejesus@gmail.com (in config) | Roy handling |
| 3 | (same as #23 — Laravel Cloud, D2) | merged into #23 |

## Release 2 — Check-in (28 Oct)

| # | Item | Why |
|---|---|---|
| 9 | ~~Class admin roles~~ — done 2026-11-07: the Admin Role at one class — its door, Who's in, Attendance, edit/retire; given on People (`tasks/2026-11-07-class-admin/`) | done |
| — | Open questions for R2: `lifeshapers_can_open` default; follow-up threshold (weeks absent) — plan §13 | plan §13 |

## Later

| # | Item | Trigger |
|---|---|---|
| 8 | ~~A satellite address must never be a reserved path~~ — done 2026-11-10: the platform's add and change-address refuse a reserved or code-shaped address (issue 09) | done |
| 25 | ~~A "delete this photo for good" action~~ — done 2026-11-07: the parent on their own page, an admin in Admin (`tasks/2026-11-07-photo-delete/`) | done |
| 26 | Storybook, as aegis (a story per component) — not now (DECISIONS 2026-10-17) | when components grow |

## Later — from Roy's first look (2026-10-17)

| # | Item | Trigger |
|---|---|---|
| 40 | **Safari: Tab does nothing after the menu sheet closes on a wide screen** (issue 12, found by the e2e update): on `/dubai/serve` and `/dubai/serve/families`, once the sheet closes because the screen turned past 1024px, focus stays on the page body; on a class's door it works. The menu journeys run on the door, so no test covers the other pages yet | before the next release |
| 39 | **"Pick your satellite" with a second church** (review, issue 08): `/pick` and the bar list every satellite a person may open, by name only — fine with one church; a whole-church app (or a platform admin over two churches) needs them per church, or the church's name beside each | with a second church |
| 38 | **Safari journeys that fail only in a full run** (Batch B, 2026-11-09): Who's in (phones), the backup password and "a Lifeshaper who registers their own family" (desktop/tablet) fail in every full `make e2e` on Safari — and in a Safari-only full run — but pass run on their own. Order-dependent state or WebKit late in a run; not yet compared with the code before Batch B (a second checkout on another port could not sign in). Also: an e2e server from 2026-11-06 has been listening on :3021 the whole time, and every run reuses it. And (issue 11 review): E2ESeeder opens 1PM/7PM on the door's Saturday once, but the server works the door's day out per request — a run crossing midnight Saturday→Sunday in Dubai would find 1PM "not open yet" | before the next release |
| 37 | **Registry import and the new satellite names** (issue 05): the import matches a family's home satellite by NAME, as the sheet spells it ("CCF DXB"); the satellites are now "CCF Dubai", "CCF Sharjah", "CCF Abu Dhabi". Before the import: rename the sheet's column values, or let the import know the old names — Roy to say | before the Registry import (#23) |
| 36 | **Lifeshaper management** (Roy 2026-11-08): Lifeshapers' schedules in the app — a scheduled Lifeshaper may open that day's Session; who serves which class; more from the stakeholder chat. A big module: its own grilling round | after #35 |
| 35 | **Classes and Sessions** (Roy 2026-11-08): recurring or one-time classes; an admin opens and closes a Session's check-in; the door at `/<session code>/checkin`; a helper for the day (`tasks/2026-11-07-ccf-nxtgen/` items 14–19). Split into issues 10–15 (`batch-c-plan.md`), questions answered 2026-11-10. **10 done 2026-11-10** (weekly or one-time classes); **11 done 2026-11-10** (an admin opens and closes a Session); **12 done 2026-11-10** (`/<code>/checkin`, Serve's first page) | building |
| 34 | **A nice home page** (Roy 2026-11-07): `nxtgen.app/` says what CCF NxtGen is and does, for parents and volunteers; maybe the launch video once Roy uploads it (`brag-output/` stays out of git) | later |
| 33 | **Satellites in the address, platform admin** (Roy 2026-11-07): `nxtgen.app/{satellite}/serve|admin`, an Admin Role per satellite, a QR code per satellite that never changes, `/platform`, the new bar (`tasks/2026-11-07-ccf-nxtgen/` items 5–13). Issues 05–09 done 2026-11-08 to 2026-11-10 | done 2026-11-10 |
| 32 | **The form and the header** (Roy 2026-11-07): "CCF NxtGen" branding, Lifestage Single/Married/KSP, allergies and illnesses as items, the birthday calendar (`tasks/2026-11-07-ccf-nxtgen/` items 1–4) | done 2026-11-07 |
| 31 | **Admin and Serve** (Roy 2026-11-06): staff addresses by service — `/ccf/admin/…` and `/ccf/serve/…`; Lifeshapers edit families in Serve; the QR poster gets the church's Links (`tasks/2026-11-06-admin-serve/`) | done 2026-11-06 |
| 29 | **Merge two families or two children** (a child registered again, legacy duplicates) — spec written 2026-11-07 — `tasks/2026-11-07-merge/spec.md`, 8 questions for Roy before building | when a re-registration happens |
| 30 | ~~UI overhaul~~ — done: the Bold theme, one UI kit for every page (`tasks/2026-10-28-ui-theme/`) | done 2026-10-28 |

## Done

| # | Item | When |
|---|---|---|
| 7, 20 | ~~Two-connection concurrency tests~~ — done: a claim link used twice at once (new and existing login), a photo upload arriving while the withdrawal is saving, the last two church admins removing each other. Found and fixed a deadlock in the last-admin count (DECISIONS 2026-11-03) | 2026-11-03 |
| 13 | ~~E2E for link expiry~~ — done: `MoveE2EClock` (an e2e-only per-request clock header) + `parent/link-expiry.spec.ts` | 2026-11-03 |
| 22 | ~~Photo sweep~~ — done: `nxtgen:photos-sweep`, daily 03:00; needs Laravel Cloud's scheduler on (#23) | 2026-11-03 |
| — | ~~"No Level" filter~~ — done: the Children and Families lists' Level filter has "No Level" (`?level=0`): children with none yet, families with such a child; the Excel downloads follow (DECISIONS 2026-11-06; plan §13 q5) | 2026-11-06 |
| 10 | ~~E2E test map~~ — done: `make test-map` (http://127.0.0.1:8790): every staff and parent route × every kind of person, each story with its tests and Run buttons (Pest, or Playwright on a picked browser); `make test-map-static` writes it as a file (DECISIONS 2026-11-06) | 2026-11-06 |
| 27 | ~~Moving-Up~~ — done: a church admin's page (a button on Children), every Active child with a Level ticked with their new Level, untick to keep back; the last Moving-Up put back for one child or everyone (DECISIONS 2026-11-06, `tasks/2026-11-06-moving-up/`) | 2026-11-06 |
| 12c | ~~E2E on an iPad in Safari, upright and on its side~~ — 15 projects (DECISIONS 2026-11-06) | 2026-11-06 |
| 12b | ~~E2E in Chrome, Safari and Firefox, upright and sideways~~ — 13 projects; Firefox in Docker on a Mac (DECISIONS 2026-11-03) | 2026-11-03 |
| 12 | ~~E2E per breakpoint~~ — done: eight projects by layout band (sm/md/lg; phones 360 and 390 in Chrome and Safari, on their side, tablets 720/768/1024, desktop), every journey on each, every screen layout-checked (no sideways scroll, menu fits on one line, ≥44px on touch); photo consent on every project with its own child; the menu row moved to lg (DECISIONS 2026-10-30) | 2026-10-30 |
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
