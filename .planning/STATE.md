# State

**Read this first at session start.** Updated: 2026-10-16 (overnight build, Release 1 in progress).

| | |
|---|---|
| **What** | NxtGen v1 — kids church app for CCF (first org), branch DXB. Generic, multi-church |
| **Deadline** | 2026-10-28. Dry run Sat 2026-10-24 (all classes) |
| **Current task** | `tasks/2026-10-14-nxtgen-v1/plan.md` — **plan signed off by Codex (plan-spar, 3 rounds); **awaiting Roy approval** + D1–D7 + §13 questions** |
| **Stakeholder doc** | https://claude.ai/code/artifact/4dcab16b-b96d-42a7-b08f-aa4f189c41e0 (+ PDF in repo root) |
| **Requirements** | `project.md` (raw, Roy's words) |
| **PoC (reference only)** | `~/projects/personal/poc/nxtgen-attendance` — not the codebase; its plan §6/§4.10 (offline) is ported into the spec |
| **House stack** | `~/projects/work/aegis/aegis` (preferred) and `~/projects/personal/current/palo` |
| **Branch** | `release-1` (plan committed; build work **uncommitted** for Roy to review) |
| **Run it** | `docker compose up -d --wait mysql redis` → `cp .env.example .env && php artisan key:generate` → `php artisan migrate --seed` → `make dev` (http://localhost:3020). Magic-link emails land in `storage/logs/laravel.log` |
| **Gate** | `make check` — green as of the last entry below |

## Build log (Release 1)

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
  nxtgen:registry-apply storage/imports/registry-review.xlsx --org=ccf`. No emails; safe to run twice.
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
