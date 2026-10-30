# State

**Read this first at session start.** Updated: 2026-10-30.

## Where things stand

| | |
|---|---|
| **What** | NxtGen — kids-church app for CCF (first church), satellite "CCF Dxb". Generic, multi-church; may grow into a whole-church app (names stay ministry-neutral) |
| **Release 1 — Registration** | **Built and committed** on `release-1`. Ships when plan §12's exit criteria pass: only TODO **#21** (staging dry run) remains — #18 (access-matrix grid + response allowlists) done in refactor 09 |
| **Release 2 — Check-in** | Slice 1 (classes, door, Attendance), slice 2 (roll call: Who's in, head count, Attendance roll call, multi-word search) and slice 3 (child history, name-card scan) built, reviewed (two-axis + test review + Astra, which approves) and pushed — `tasks/2026-10-26-check-in/`, `tasks/2026-10-28-roll-call/`, `tasks/2026-10-28-slice-3/` |
| **Name cards** | Built, reviewed (two-axis + test review + Astra, which approves) and pushed: staff report a lost/damaged card, a free first card for every new child, the admin Cards page (paid, approve, refuse, printed, given), print lists with an Excel download — `tasks/2026-10-29-name-cards/` |
| **Next step** | Roy tries roll call, child history, name-card scanning and the name cards module (`php artisan migrate`; Cards is in the admin menu). Then confirms the Claude calls in DECISIONS 2026-10-28 and 2026-10-29. Open: follow-up list (skipped for now), TODO #29 merge, #27 Moving-Up (before Feb), offline check-in |
| **Waiting on Roy** | Laravel Cloud + mail provider + Registry CSV (TODO #23); "No group" filter yes/no (plan §13 q5); a real-iPhone photo try (TODO #16) |
| **Gate** | `make check` green — 2240 Pest + 173 arch, 447 Vitest, PHPStan L10 · `make e2e` green — 377 runs on 8 breakpoint projects (phone 360/390 + Safari, tablet 720/768/1024, phone landscape, desktop), 2 skips |

## Where to look

| File | What it holds |
|---|---|
| `tasks/2026-10-14-nxtgen-v1/plan.md` | The approved plan; §12 has the Release 1 exit-criteria status |
| `../docs/DECISIONS.md` | Every decision after the plan, newest first — **outranks the plan** |
| `tasks/2026-10-14-nxtgen-v1/review.md` | Reviews 1–4: intent ledgers, debt ledgers, rulings |
| `tasks/2026-10-14-nxtgen-v1/spar.md`, `review-spar.md` | The Codex debates behind the plan and the reviews |
| `TODO.md` | Everything not built, grouped by when it is needed (numbers are stable) |
| `SECURITY.md` | Known security items; no open **must** items (#4, #5 done 2026-10-17) |
| `../README.md` | Run it locally, the checks, browser tests, starting production |

## Working rules (Roy)

Test-first (TDD) · security items go on `SECURITY.md` and do not block · E2E for every built screen · Codex review-spar
after each feature batch · stakeholder-facing text in plain words (ELI5) · commit when Roy says so.

| | |
|---|---|
| **Stakeholder doc** | https://claude.ai/code/artifact/4dcab16b-b96d-42a7-b08f-aa4f189c41e0 (+ PDF in repo root) |
| **Requirements** | `docs/archive/requirements-raw.md` (raw, Roy's words); `docs/NxtGen Kids Church App - What We Are Building.pdf` |
| **PoC (reference only)** | `~/projects/personal/poc/nxtgen-attendance` — not the codebase |
| **House stack** | `~/projects/work/aegis/aegis` (preferred) and `~/projects/personal/current/palo` |
| **Run it** | `docker compose up -d --wait mysql redis` → `cp .env.example .env && php artisan key:generate` → `php artisan migrate --seed` → `make dev` (http://localhost:3020). Magic-link emails land in `storage/logs/laravel.log` |

## Build log (Release 1) — history, oldest first

- **Scaffold:** Laravel 12.70 + Lucid v2 + Spatie Data + TS transformer + Inertia 3 / Vue / Pinia / Tailwind 4,
  tooling ported from the PoC (Makefile, gate, arch tests, discipline test). PHPStan **level 10** via typed
  Lucid stubs (`stubs/Lucid.stub`: every Job declares `@extends BaseJob<Result>`, so `$this->run()` is typed).
  Docker: MySQL 33316, Redis 36389 (Mailpit optional, `--profile mail`).
- **Schema:** orgs, branches, role_assignments, groups, families/guardians/children with DB-enforced invariants
  (one family per user per church, one login per family, primary guardian in family) — proven by tests pinned
  to constraint names. family_claims (one pending per email per church), magic_links, duplicate_candidates, notes.
- **Magic links:** hashed tokens, single use (atomic conditional UPDATE), bound to email + credential version /
  claim revision; login 2 h, verify/claim 7 days.
- **RegisterFamilyOperation:** the one registration path — Registered / AlreadyRegistered / ClaimSent /
  ClaimResent; group suggestion by age on the reference date + gender; duplicate candidates (phone, name+DOB).
- **Auth service:** /login, POST /login/link (same answer for unknown emails, throttled per email + IP),
  /magic/{login|verify|claim}/{token}, AcceptFamilyClaimOperation, logout.
- **Landing:** one decision point — parent → /{church}/family, staff → /{church}/admin, both → choose, none → "nothing here yet".
- **Public registration** `/{church}/register` (+ thanks page): today's Google Form fields + home satellite, several
  children, plain-word validation. Verified end to end in Chrome (found and fixed an autofill bug in ChildFieldset).
- **Family page** `/{church}/family`: view family + children (incl. health), add a child, edit a child (not status/group).
- **Admin** `/{church}/admin`: AccessScope (church / satellite / class admin, Lifeshaper); scoped family list with
  "email not confirmed" filter + Excel export; **search-first staff registration** (church-wide, masked emails);
  add a child to any found family; family page; resend confirmation; child status; v1 email correction.
- **Registry import:** `php artisan nxtgen:registry-propose storage/imports/registry.csv` → review
  `storage/imports/registry-review.xlsx` (Families / Quarantine / Reconciliation) → `php artisan
  nxtgen:registry-apply storage/imports/registry-review.xlsx --church=ccf`. No emails; safe to run twice.
  Export the **Registry** tab as CSV into `storage/imports/` (git-ignored — personal data).
- **Fixed on the way:** `make check`'s migrate-check was wiping the WORKING database (no `.env.testing`) — added.
- **Known gap:** a mixed group (Level 1) needs a per-gender "next group" for Moving-Up → TODO.md.
- **Codex diff review:** 6 rounds (ceiling), 11 findings fixed (6 high). Not signed off — see spar.md bottom.
  Link GETs now show a Continue page; exports are text-only; public registration says the same thing for every outcome.
- **Not built yet (Release 1):** Playwright e2e harness; duplicate-candidates review screen; Laravel Cloud deploy.
- **review-spar (2026-10-15):** `tasks/2026-10-14-nxtgen-v1/review.md` + `review-spar.md`. 4 rounds; Codex diff
  review now **approves**. 10 more fixes (resend scope, honest send results, import accounting/groups/re-validation,
  registration mapping, consolidations). **Two decisions pending** (D-R1 import reruns, D-R2 staff provisioning) and a
  remaining R1 build list.
- **Gate:** `make check` green — 160 Pest (+25 arch) and 57 Vitest tests, PHPStan level 10.
- **Roy's decisions (2026-10-15):** D-R1 import once, app = source of truth; D-R2 build People. **Admin service → Org**,
  URLs `/{church}/…` (no `/admin`): `/ccf/families`, `/ccf/families/new` (staff register; public stays
  `/ccf/register`), `/ccf/people`. Reserved top-level paths (`/up` was being swallowed by the church wildcard — fixed,
  tested in `OrgRoutingTest`).
- **People (TDD):** `AccessScope::canManagePeople/canGrant` (ceiling: church admin anything; satellite admin
  Lifeshaper in own satellite); list, add (existing login gains the role; invite = sign-in link saying the role),
  remove (kept for record; never the last church admin, counted under lock). Menu link via shared `orgNav`.
  Revert-checked: ceiling and last-admin guard.
- **Gate:** `make check` green — 184 Pest (+25 arch), 62 Vitest, PHPStan level 10.
- **E2E (2026-10-15):** Playwright on its own DB (`nxtgen_e2e`, `.env.e2e`) and port 3021; `/__e2e/health` must answer
  before a run (only an e2e server does). Sign-in through real email links: under APP_ENV=e2e every email is copied
  to `storage/app/e2e/mailbox.jsonl` (E2EServiceProvider). Staff sign in once (`auth.setup.ts`). Journeys:
  parent registers → confirms → family page → adds child; returning parent sign-in; Lifeshaper search-first add
  child / register; admin adds Lifeshaper → invite → sign in → role removed → 404; satellite-admin scope.
  iPhone Safari + desktop Chrome: **17 passed**. Revert-checked (grant ceiling). CI e2e job switched on.
  `E2EWorldTest` keeps `world.ts` and E2ESeeder in step.
- **E2E complete for built pages (2026-10-15):** 29 runs (iPhone Safari, Android Chrome, desktop Chrome), green twice.
  Added: already-registered, claim + Choose, NothingYet, sign-out, used link, resend, correct email, status, filter,
  export, welcome. Fixed: parents had no sign-out (SignOutButton); per-device limit configurable (e2e only raised);
  e2e DB guard. Not e2e-able yet: parent child edit (no UI), link expiry. Review 2 paused — Codex rounds pending.
- **Gate:** `make check` green — 192 Pest (incl. 25 arch), 63 Vitest, PHPStan level 10.
- **R1 build, after 101d728 (2026-10-15, uncommitted):**
  - `ChurchSeeder`: production church/satellites/groups/first admin from NXTGEN_* settings; `db:seed` in production
    never makes the demo logins (password "password") — revert-checked. README "Starting production".
  - Form parity (B6): allergy question is an explicit yes/no (a "no" keeps no details), nickname required, DGroup
    area + leader phone, lifestage, ministry on the form.
  - Editing (B5): parent edits family + own details + children (PUT /{church}/family, …/family/children/{id});
    staff who may edit: PUT /{church}/families/{id}, /{church}/children/{id}. Full health on every child card.
    One owner of field rules: FamilyDetailsInput / GuardianDetailsInput (registration spreads them); shared
    GuardianFieldset + FamilyFieldset (RegisterFamilyForm 267 → 141 lines). Edit gates revert-checked.
  - `make check` lint now really covers tests/e2e (Makefile called eslint directly).
  - Gate: 215 Pest (incl. 25 arch), 81 Vitest, PHPStan L10; E2E 33 runs green.
  - Duplicates (B7): `/{church}/duplicates` side by side (lite view), dismiss / confirm (flag for merge, nothing
    merged), reviewers = who may edit the new family; noted on the family. System notes (Note model, morph map
    `family`/`child`): email corrections, status changes, duplicate answers → "History" on the staff family page.
    List search + filters (satellite, status, group, unconfirmed, duplicates; never widens scope), export matches.
    One search rule: `Family::scopeMatching` (door search + list).
  - Guards: `TenancyArchTest` (tenant queries must `where` organization_id/family_id; 4 cross-church reads listed
    with reasons) and `AccessRowTest` (every Org/Family Feature declares its matrix row) — both proven by removal.
  - `docs/DECISIONS.md` started.
  - Gate: 237 Pest (incl. 31 arch), 87 Vitest, PHPStan L10; E2E 34 runs green.
- **R1 build, after 78e9cdd (2026-10-15):**
  - Remembered landing (plan §4.5): POST /home/choice remembers My family / Serve on the device (cookie, honoured
    only while still an option, exact match — no off-site redirect, revert-checked); "Switch" link when there is
    more than one place to go (`auth.canSwitch`).
  - Staff backup password (D7): /account/password for anyone serving somewhere; "Use a password instead" on sign-in;
    works only while they serve (parents never), one answer for every failure, 5 tries/min per email; revert-checked.
  - QR poster: /{church}/poster (any staff), server-drawn SVG QR (bacon/bacon-qr-code) of the registration URL.
  - Sentry wired (sentry/sentry-laravel), OFF until SENTRY_LARAVEL_DSN is set; `send_default_pii` false (not from
    .env); `SentryScrubber` before_send strips request body/query/cookies/headers, login email/name/IP, breadcrumb
    data, extras — unit-tested.
  - Gate: 262 Pest (incl. 31 arch), 90 Vitest, PHPStan L10; E2E 38 runs green.
- **Photos (2026-10-16, Roy: parent or Lifeshaper, take or upload):** private `photos` disk (local; S3 private bucket by
  PHOTOS_DISK=s3); re-encoded upright/600px/no EXIF (fixture with orientation + GPS proves it); only after the PARENT
  agrees (consent_at/by, new `photo_consent_by` column), staff never agree [later changed: staff may record an in-person yes; withdrawal hides]; parent takes consent back → photo
  deleted; view = signed 30-min link AND viewer check (own family or staff who can see the family), no-store.
  Revert-checked: upright, viewer check, staff consent check. Production satellite default "CCF Dxb"; Sentry off.
  Gate: 274 Pest (incl. 31 arch), 95 Vitest; E2E 41 runs green.
- **Review 3 (2026-10-16, uncommitted):** review-spar over 7fe14cd..3772d51 — Codex round 2 agrees. Fixed: S3 adapter,
  Sentry message scrubbing (was documented-only), photo consent-only path + locked save/withdraw + every-file
  withdrawal + consent-checked reads, production mailer guard, create-only ChurchSeeder reruns, import "awaiting
  claim" report, AccessRow on the photo route, CI release-*. Logged: TODO #17–#22 (#18 and #21 before R1 ships).
  Gate: 285 Pest, 99 Vitest, PHPStan L10; E2E 41 green.
- **R1 features complete (2026-10-16):** children list `/{church}/children` (scoped, search + group/status/satellite
  filters, allergy flag; shared `RegistrationListFilters`) and church-admin group correction (`PUT …/children/{id}/group`:
  this church's group, fits the child's gender, noted) — revert-checked; E2E found and fixed a refused-group message
  showing under every child (`ChildAdminControls`). Staff can record a parent's in-person photo consent. First admin
  blivejesus@gmail.com. Gate: 298 Pest, 103 Vitest, PHPStan L10; E2E 43 green.
- **Review 4 (2026-10-16, uncommitted):** Codex agrees after 4 rounds. Fixed: blank PHOTOS_BUCKET fallback, retired
  groups refused, consent history on every path (atomic), children search by parent contact. Owner questions: staff
  consent confirm step, list paging, "no group" filter (+ withdrawal delete vs hide). Gate: 304 Pest, 103 Vitest; E2E 43.
- **Roy's answers built (2026-10-16):** staff consent confirm step; lists paged (50); withdrawal hides the photo (kept,
  never shown; returns on a new yes). Gate: 310 Pest, 106 Vitest, PHPStan L10; E2E 43 green.
