# NxtGen v1 — Plan

**Status:** approved (Roy, 2026-10-15) · **Release 1 built** (2026-10-16; exit criteria §12 pending TODO #18, #21) · Release 2 not started · **Date:** 2026-10-15, updated 2026-10-16
**Product doc (stakeholders):** https://claude.ai/code/artifact/4dcab16b-b96d-42a7-b08f-aa4f189c41e0 (PDF in repo
root) · **Raw requirements:** `project.md` · **Debate:** `spar.md` (same folder) · **Changes since approval:**
`docs/DECISIONS.md` (newest first — it outranks this plan where they differ) · **Reviews:** `review.md` (1–4)

NxtGen is a kids-church app for CCF Dubai's kids ministry, built for any church. Two releases:
**Release 1 — Registration** (target ~20 Oct, conditional on its exit criteria) and **Release 2 — Check-in**
(28 Oct). Online-first; offline is a later round.

---

## 0. Decisions

### Technical choices (Roy: "start the development" — recommended options taken; change any of them by saying so)

| # | Decision | Recommended | Alternative |
|---|---|---|---|
| D1 | Database | **MySQL 8.4** (palo + PoC on Laravel Cloud; generated-column invariants proven there) | Postgres 16 (aegis) |
| D2 | Hosting | **Laravel Cloud** (TLS for camera, managed MySQL/Redis/queue/Reverb/object storage) | Hetzner + Compose (aegis) |
| D3 | WebSockets | **Reverb** (Pusher protocol; aegis `useEcho` ports as-is) | Soketi (aegis) |
| D4 | Realtime | **Event = cue to refetch** + 30 s polling fallback; never load-bearing | Polling only (palo) |
| D5 | PHPStan | **Level 10** from day one | Level 8 |
| D6 | Storybook | **No** — `.test.ts` siblings (palo/PoC) | Storybook (aegis) |
| D7 | Staff login | **Magic link + optional password** for staff; magic link only for parents | Magic link only |

### Settled with Roy / stakeholders

| Topic | Settled as |
|---|---|
| Tenancy | Org = church. Branch = "Satellite" (UI label per org). Families/children belong to the org; a family has a **home satellite** |
| Visibility | Staff work within their scope. **Door search covers the whole org** (same umbrella; prevents double registration) with a minimal projection |
| Roles | Scoped **church admin / satellite admin / class admin** + **Lifeshaper**; a user may hold several |
| Health data | Visible to **all Lifeshapers/admins of the org** and the **child's own family**; never to other parents |
| Registration | Every child is registered before check-in. Parent self-registration preferred; **staff may register** after searching, email required but may stay **unconfirmed**; unconfirmed never blocks check-in |
| Login | **One login per person, no role picker**; a user can be a parent and a Lifeshaper. **One login per family** in v1; second-parent invite later |
| Groups | Age + gender, per org, **stored**; changed at **Moving-Up** (yearly, admin-run), or corrected by a **church admin** (§3.2). App shows a "ready to move up" indicator |
| Child status | `active` / `inactive` / `graduated` (finished NxtGen 5–12 → Elevate) |
| Notes | One **polymorphic** notes table (Roy: "rather than having request_notes, we just have notes"). R1: system audit notes on families; R2: staff observations on children |
| Area | Free text in v1; shared list + map picker later |
| Cards | Today's Code 39 name barcodes until Moving-Up (Feb), then QR + reprint. First card free, replacement AED 10 |
| Reports | In-app lists + Excel export; no Sheets sync |
| Existing data | Loaded **once** from the Google Sheet through a reviewed import; the app is the source of truth after that (D-R1) |
| Photos | A parent **or a Lifeshaper** takes or uploads a child's photo, only after consent. Consent is given by the parent, or recorded by a Lifeshaper the parent told in person (with an "Are you sure?" step); every change is on the child's history. Withdrawal **hides** the photo (kept, shown to nobody, back on a new yes). Private bucket; signed 30-minute links (Roy 2026-10-16) |
| Staff pages | The **Org** service at `/{church}/…` — no `/admin` in URLs; for Lifeshapers too (Roy 2026-10-15) |
| Hosting | **Laravel Cloud** + MySQL + private S3 bucket; Sentry off for now; production starts with one satellite, "CCF Dxb"; first admin blivejesus@gmail.com (Roy 2026-10-16) |
| Later | Offline + payload round · merge · card requests/printing · area map · kiosk · roll call · head count · pickup · Squad Leader notes · parent attendance/notes · Moving-Up action (before Feb) |

---

## 1. Stack

| Layer | Choice |
|---|---|
| Runtime | PHP 8.4, **Laravel 12** (not 13 — Lucid v2 resolves to v1.0.11 on 13) |
| Architecture | `lucidarch/lucid ^2.0`: Controller → Feature → Job; Operation only where domains cross |
| DTOs / types | `spatie/laravel-data ^4` (`*Input` in `Services/*/Data`, `*Data` with `#[TypeScript]` + `fromModel()` in `Domains/*/DataObjects`); `spatie/laravel-typescript-transformer` |
| Frontend | Inertia 3 · Vue 3.5 · TS strict · Pinia (client state only) · Reka UI 2 · Tailwind 4 · lucide · Ziggy |
| Realtime | Reverb + `laravel-echo` + `pusher-js` (Release 2) |
| Scanning | `@zxing/browser` (Code 39 + QR), native `BarcodeDetector` first (Release 2) |
| Excel | `openspout/openspout` writer (new rendering over scoped report queries) |
| Photo | single private `children.photo_path` on object storage, adapted from aegis avatar flow (no Media Library) |
| Mail / errors | Resend · Sentry with a PII scrubber (no child data in events) |
| Tests / quality | Pest 4 (+arch) · Vitest · Playwright · Pint · Larastan L10 · ESLint 9 · Prettier · vue-tsc |
| Local dev | Compose: app, node, mysql:8.4, redis:7, reverb, mailpit |

---

## 2. Layout

```
app/Data/Models · app/Domains/{Domain}/{Jobs,DataObjects,Tests} · app/Operations · app/Foundation
app/Services/{Public,Auth,Family,Org,Platform}/{Data,Features,Http,Providers,routes,STORIES.md}
app/Enums · app/Console/Commands
resources/js/{pages,components/{atoms,molecules,organisms,layout},composables,stores,types,tests} + REGISTRY.md
.planning/{STATE.md,TODO.md,tasks/…} · docs/DECISIONS.md
```

**Org service (Roy, 2026-10-15; was "Admin" + "Door"):** every church staff page, for admins AND Lifeshapers —
what each person may do is AccessScope's answer, never the URL. URLs start with the church slug and carry no
`/admin`: `/ccf/families`, `/ccf/families/new`, `/ccf/people`; satellite pages (R2) nest: `/ccf/{satellite}/check-in`.
The slug is a wildcard at the top of the address, so fixed paths (`/login`, `/home`, `/magic`, `/up`…) are
reserved (`RouteOrganization::RESERVED`) and the Org routes register last. The app may grow into a whole-church
app (events, DGroups, NxtGen, Elevate); names stay ministry-neutral.

**Domains:** Organization (orgs, branches, role assignments, access) · User · Auth (magic links) · Family
(families, guardians) · Child · Group · Event · Attendance · Card · Note · Report · Registry (import).

**Operations (cross-domain only):** `RegisterFamilyOperation` (User + Family + Child + Auth — the **single**
registration path for parent, staff and import), `AddChildToFamilyOperation` (Family + Child + Group).
Check-in is never an Operation.

---

## 3. Tenancy, scope and authorization

### 3.1 Resolution vs authorization (two separate steps)

1. **`ResolveOrganization`** (every org route): `{org:slug}` → org on the request. No membership check.
2. Route-group authorization:
   - **Public** (`/{org}/register`, verify links): none.
   - **Family** (`/{org}/family/…`): **`RequireGuardian`** — the user is the linked guardian of a family in this
     org (`GetFamilyForUserJob`). Else 404.
   - **Door / Admin**: **`RequireAssignment`** — at least one active role assignment in this org. Else 404.
   - **Platform**: `is_platform_admin`.
3. Nested routes use `->scopeBindings()`; **every id in a request body** (family, child, group, branch,
   event series, note target) is validated against the org **and** the actor's scope in the Input/Feature.

Not session-based (palo's two-tab bug). `TenancyArchTest` (palo) guards that tenant queries mention
`organization_id`; it is a guardrail, not proof — authorization is proven by the matrix tests below.

### 3.2 Who may do what (the matrix; one owner: `AccessScope`, built from assignments, consulted by Features)

| Action | Church admin | Satellite admin (B) | Class admin (S) | Lifeshaper (B) | Parent |
|---|---|---|---|---|---|
| Door search (org-wide lite projection) | ✓ | ✓ | ✓ | ✓ | — |
| See selected child's door detail incl. health | ✓ | ✓ | ✓ | ✓ | own children |
| Register a new family + child | ✓ | ✓ | ✓ | ✓ | own (self) |
| Add a child to an existing family found by search | ✓ | ✓ | ✓ | ✓ | own family |
| Edit family/guardian/child details | ✓ | families with home branch B | — | — | own family (not status/group) |
| Resend confirmation link | ✓ | home branch B | — | ✓ (any family found) | — |
| Correct family contact (§4.4) | ✓ | home branch B | — | — | — |
| Set child status | ✓ | home branch B | — | — | — |
| Set child group (outside Moving-Up) | ✓ | — | — | — | — |
| Manage groups, satellites | ✓ | — | — | — | — |
| Manage classes / events of B | ✓ | ✓ | own series S | — | — |
| Open / close an event | ✓ | events of B | events of S | events of B (if `lifeshapers_can_open`) | — |
| Check in / undo | ✓ | events of B | events of S | events of B | — |
| Attendance reports + Excel | ✓ | B | S | — | — |
| Grant roles | ✓ (any) | Lifeshaper/class-admin within B only | — | — | — |

- "Home branch B" = `families.home_branch_id = B`. Class scope S = the event series and its events'
  attendance (past and present).
- **Role-grant ceiling:** a granter can only grant a role ≤ their own, within their own scope.
- Every Admin/Door Feature declares its matrix row; an arch test fails if a Feature has none. Feature tests cover
  each row for: anonymous, parent-only, staff-only, parent+staff, revoked staff, other-branch staff, other-org
  user.

### 3.3 Projections (field allowlists — what leaves the server)

| Projection | Fields | Used by |
|---|---|---|
| `ChildSearchIndexData` | id, display/first/middle/last name, nickname, family key, group id, home branch name, status, scan keys | door search index (props) |
| `ChildDoorDetailData` | + allergies, illnesses, fears, dislikes, parent notes, guardian names + phones, masked family email | selected child at the door (fetched on select, authorized) |
| `FamilyAdminData` / `ChildAdminData` | full record within scope | admin |
| `FamilyPortalData` | own family only | family page |
| Excel rows | report-specific allowlist; no health fields except allergies on attendance sheets | exports |

Response-level tests assert forbidden fields and photo URLs are absent for each actor type. Photo URLs are
short-lived signed URLs issued only after authorization.

---

## 4. Users, guardians, families

### 4.1 Schema

```
users        id, name, email UNIQUE, email_verified_at?, credential_version int, password?, is_platform_admin,
             remember_token, last_login_at
guardians    id, organization_id, family_id, user_id?, relationship, first_name, last_name, phone, email?,
             ministries json, can_pick_up
             UNIQUE(organization_id, user_id)                ← one family per user per org (NULLs allowed)
             + generated login_family_key = (CASE WHEN user_id IS NOT NULL THEN family_id END) UNIQUE
                                                              ← one login per family (v1; drop index for invites)
             UNIQUE(family_id, id)                            ← target for the composite FK below
families     id, organization_id, home_branch_id, primary_guardian_id?, …
             FK (id, primary_guardian_id) → guardians(family_id, id)   ← primary guardian belongs to this family
role_assignments  id, organization_id, user_id, role, scope_type (organization|branch|event_series), scope_id,
             is_active, granted_by   UNIQUE(user_id, role, scope_type, scope_id)
```

- **No `family_id` on users.** Parent access = guardian link; staff access = role assignment; a user can have
  both.
- Family email = primary guardian's linked user's email (or the guardian's `email` while unlinked, §4.3).

### 4.2 Registration outcomes (one path: `RegisterFamilyOperation`, under `GET_LOCK('reg:'+normalised_email)`)

| Situation | Outcome |
|---|---|
| Email's user is already a guardian in this org | **Not created.** "Already registered" + login link sent to that email |
| Email has a user with no family here (e.g. a Lifeshaper) **and no pending claim** | Family created; primary guardian **unlinked**, `email` stored; a `family_claims` row (pending) + **claim link** sent; linked only when that email accepts |
| A pending claim already exists for this email in this org | **Not created.** The existing claim is re-sent ("check your email to finish") |
| Email unknown (no user, no pending claim) | User created (no password, unverified) + family + primary guardian **linked**; `verify_email` link sent |
| Possible child/family duplicate (same guardian phone, or child first+last+DOB) | Registration proceeds; a `duplicate_candidates` row (open) is created for staff review — never auto-merged |

```
family_claims         id, organization_id, family_id, guardian_id, email_normalised, revision int,
                      status (pending|accepted|revoked), created_at, resolved_at?
                      + generated pending_claim_key = (CASE WHEN status='pending'
                          THEN CONCAT(organization_id,':',email_normalised) END) UNIQUE   ← one pending claim per email per org
duplicate_candidates  id, organization_id, subject_type (family|child), subject_id, candidate_id,
                      reason (same_phone|same_name_dob|same_email), status (open|dismissed|confirmed),
                      resolved_by?, resolved_at?   UNIQUE(subject_type, subject_id, candidate_id)
```

**Claim acceptance** takes the same named email lock, rechecks that the user has no guardian in this org, that the
claim is `pending` and its revision matches the token, then atomically consumes the token, links the guardian
(creating the user first if none exists for that email) and marks the claim `accepted`. If the user gained a family
here meanwhile → claim `revoked`, "already registered" shown. Tests: repeated pending registrations, claim
acceptance racing registration, claim for an email with no user yet.

Duplicate candidate search reuses the PoC/door normaliser; it produces candidates, never identity decisions.
Tests: concurrent same-email registrations, shared phones, namesakes, staff-becomes-parent, repeated claims.

### 4.3 Magic links

```
magic_links  id, user_id?, email_snapshot, purpose (login|verify_email|claim_family), family_claim_id?,
             claim_revision?, credential_version?, token_hash, expires_at, used_at?, ip, user_agent
```

- 32 random bytes, SHA-256 stored. **Login/verify links** bind to `user_id` + `email_snapshot` +
  `credential_version`. **Claim links** bind to `family_claim_id` + `claim_revision` + `email_snapshot`
  (no user needed). Redemption fails on any mismatch.
- Consumed with one conditional `UPDATE … SET used_at = now() WHERE id=? AND used_at IS NULL AND expires_at > now()`
  (affected rows = 1 or reject) — safe under concurrent clicks.
- TTL: **login 2 hours** (Roy); verify/claim 7 days. **Account email change** (only §4.4's first case) bumps that user's
  `credential_version` and invalidates their remember tokens. **Family contact change** revokes the family's
  claims (status `revoked`, revision++) and never touches any account's credentials. Redemption regenerates
  the session.
- Login requests answer identically for unknown emails; the email to an unknown address says "register here".
- Tests: concurrent redemption, wrong purpose, expiry, old-email link after account email change, contact
  correction A→B→C then every older claim link rejected while A's own account is unchanged.

### 4.4 Correcting a family's contact (admin in scope)

**v1 (Roy: security must not stop us):** an admin may update the family email only in the first case below; in
the second case the screen says "this email is also a staff login — ask a church admin" and nothing changes. The
re-point + claim flow is listed in `.planning/SECURITY.md` for later.


- If the primary guardian's user is **unverified, family-only, single-org, no assignments, not platform admin**:
  update that user's email, bump `credential_version`, send a new verify link.
- Otherwise the user's account is **never edited**: the primary guardian is **unlinked**, its `email` set to the
  corrected address, the family's open claims are revoked, and a new claim + link is sent (§4.2 path). The old
  user keeps every other access.
- Audited as a **system note** on the family (`notes.kind = system`). Test: satellite admin correcting a family whose primary user is a church admin.

### 4.5 Landing (one `LandingFeature`, PoC lesson)

Orgs = guardian links ∪ active assignments. None → "nothing here yet" page with register link. Several orgs →
pick church. Then: guardian only → Family; assignments only → Staff dashboard; both → one-time choice
"My family / Serve", remembered, switchable from the menu. Staff password login (D7) is a backup only.

---

## 5. Data model (rest)

`timestampTz`; every column `->comment()`; create-migrations editable until the first deploy holds real data.

```
organizations  id, name, slug, timezone, currency, moving_up_reference_date, settings json
               (satellite_label, lifeshapers_can_open, replacement_card_fee_minor=1000, first_card_free)
branches       id, organization_id, name, slug
groups         id, organization_id, name, sort, min_age, max_age, gender? (null = mixed), next_group_id?, is_active
               ranges validated non-overlapping per gender within the org; next_group same org

families       id, organization_id, home_branch_id, primary_guardian_id?, area_text?, fb_account, wants_fb_group,
               in_dgroup, dgroup_area_text?, dgroup_leader_name, dgroup_leader_phone, wants_dgroup, ministries json,
               lifestage?, preferred_event_series_ids json, privacy_consented_at?, registered_by_user_id?,
               registration_source (self|staff|import), notes
children       id, organization_id, family_id, group_id?, first_name, middle_name?, last_name, nickname, gender,
               date_of_birth, allergies, illnesses, dislikes, fears, parent_notes, status, status_changed_at/by,
               group_set_at/by, photo_path?, photo_consent_at?, photo_consent_by?, registration_source

event_series   id, organization_id, branch_id, name, weekday, starts_at, ends_at, is_active
events         id, organization_id, branch_id, event_series_id?, occurrence_date?, name, starts_at, ends_at,
               opened_at?, opened_by?, closed_at?, closed_by?
               UNIQUE(event_series_id, occurrence_date)
attendances    id, organization_id, event_id, child_id, checked_in_at, checked_in_by, undone_at?, undone_by?,
               method_hint?   UNIQUE(event_id, child_id)

notes          id, organization_id, noteable_type (morph map: family, child), noteable_id, author_id?, kind (system|observation),
               body, visibility (staff|family)
registry_import_mappings  id, organization_id, source_child_key UNIQUE per org, approved_family_key,
               child_id, family_id, source_hash, imported_at
```

- **Group assignment rule (registration + import):** age on `moving_up_reference_date` + gender → exactly one
  active group, else "unassigned" (flagged). Imported reviewed groups are kept as-is. "Ready to move up" =
  derived (age on the reference date > group max_age); never acted on automatically.
- **Status at the door:** active → check in; inactive → "reactivate?" confirm (staff), then check in;
  graduated → refused with reason; (merged → later).
- **Cards (Release 2):** legacy Code 39 name barcodes have no `id_cards` row. Scan keys
  `[norm(nickname+last), norm(first+middle+last), norm(first+last)]`, `norm` = strip diacritics → uppercase →
  A–Z0–9; one hit → show child, several → pick list, none → search pre-filled; the child is always confirmed
  before check-in. A name barcode cannot be revoked and may stop resolving after a name correction (accepted).
  Same `norm` in TS and PHP with a shared parity fixture. `card_scan_events` records scans. QR + `id_cards` +
  card requests arrive with the Feb reprint.

### Database-enforced invariants

| Invariant | How |
|---|---|
| One family per user per org | `guardians UNIQUE(organization_id, user_id)` |
| One login per family (v1) | generated `login_family_key` UNIQUE |
| Primary guardian belongs to its family | composite FK `families(id, primary_guardian_id) → guardians(family_id, id)` |
| One occurrence per series per date | `events UNIQUE(event_series_id, occurrence_date)` |
| One check-in per child per event | `attendances UNIQUE(event_id, child_id)` + `insertOrIgnore` |
| One role per scope | `role_assignments UNIQUE(user_id, role, scope_type, scope_id)` |
| One mapping per source child | `registry_import_mappings UNIQUE(organization_id, source_child_key)` |
| One pending claim per email per org | generated `pending_claim_key` UNIQUE |
| One candidate pair | `duplicate_candidates UNIQUE(subject_type, subject_id, candidate_id)` |

### Concurrency protocol (one order everywhere)

`event row FOR UPDATE` → child rows `FOR UPDATE` in id order. Check-in, undo, status change at the door and close
all take it; close and check-in re-check `closed_at`/authorization **under the lock**. Registration uses the
named lock on the normalised email. Real-MySQL concurrent tests: duplicate scheduler runs, check-in vs close,
mixed-success family check-in, undo vs re-check-in, concurrent registration.

---

## 6. Features by release

### Release 1 — Registration

- **Parent registration** (`/{org}/register` + printable QR poster): today's Google Form fields + home satellite;
  required: child name, nickname, gender, birthday, allergies (yes/no + details); parent name, phone, email, home
  satellite, privacy consent. §4.2 outcomes. Group assigned by §5 rule.
- **Staff registration** (Door/Admin "Register a family"): search first (org-wide index + guardian phone/email
  lookup); found → add child; not found → `RegisterFamilyOperation`; "email not confirmed" badge with
  **Resend** and **Correct contact** (§4.4).
- **Auth:** magic links (login, verify, claim), staff password backup, landing (§4.5).
- **Family page (minimal):** view family + children, add a child, edit details (not status/group).
- **Admin registration view (scoped):** families/children lists + search + filters (status, group, satellite,
  unconfirmed email, open duplicate candidates), view/edit within scope, set status, dismiss/confirm duplicate
  candidates (confirm = flag for the later merge), family system notes shown, Excel export of the registration
  list.
- **People** (`/{org}/people`, Roy D-R2 2026-10-15): list who serves; add someone by name + email (an existing
  login, e.g. a parent, just gains the role) with one role within the grant ceiling, emailed a sign-in link;
  remove a role (kept for the record; never the last church admin).
- **Platform:** the church, its satellites and its first church admin come from the seeder in R1; screens later.
- **Registry import** (§7).
- **Children list** (`/{org}/children`) beside the families list; both **paged, 50 per page**; Excel keeps every
  filtered row (Roy 2026-10-16).
- **Child group correction** by a church admin (§3.2), gender-checked, noted on the history.
- **Photos with consent** (see §0 "Photos").

**Built (2026-10-16), all of the above** — commits `7fe14cd..027f555` on `release-1`. Reviewed four times with
Codex (`review.md`, `review-spar.md`). What is NOT built yet is listed in `.planning/TODO.md`.

### Release 2 — Check-in

- Classes (event series) + idempotent generation in org timezone (8 weeks ahead; edits affect future unopened
  events only) + one-off events + open/close.
- Door: org-wide `ChildSearchIndexData` in props + PoC ranker (one search path); select → `ChildDoorDetailData`;
  filters; family grouping; check in one child or a family (per-child outcome map); undo; scanning (§5 cards).
- Realtime refetch (D3/D4).
- Reports + Excel: attendance per class, child history, follow-up (active kids absent N weeks), moving-up list.
- Staff observation notes on a child (`kind = observation`).

### Later

Merge (own design: dependent-record matrix, collisions, locks) · card requests/printing/QR · offline + payload
round · area map · kiosk · roll call/head count/pickup · Squad Leader notes · parent attendance/notes ·
Moving-Up action · second-parent invite.

---

## 7. Registry import (one pipeline)

`php artisan nxtgen:registry propose {csv}` → `review.xlsx` → human review → `php artisan nxtgen:registry apply {xlsx}`.

1. **Parse** with streamed CSV parsing adapted from aegis `ParseCsvRowsJob` (quoted multiline fields safe).
2. **Validate + normalise** (names, full phone in E.164 with original kept, lowercase email, one birth date,
   status: `DUPLICATE` dropped, `FORWARDED TO ELEVATE` → graduated, `WENT HOME`/`remove` → inactive).
3. **Propose** families: group by normalised email. Phone matches across different emails are **suggestions
   only**, never automatic unions. Each row gets a stable `source_child_key` = hash(parent email + child first name + birthday) — the Registry tab
   has no Form timestamp (review 2026-10-15). Written into the review file and persisted in `registry_import_mappings`;
   a corrected row (new key) is caught by the name + birthday guard, with a reviewer "Distinct child" override.
4. **Review file:** one sheet of proposed families with `approved_family_key`, chosen primary guardian + email,
   home satellite (from "Attending worship schedule"; "Either …" flagged), group; a **quarantine** sheet
   (missing/invalid email, conflicts); a **reconciliation** sheet (input rows = accepted + dropped + quarantined).
   Reviewer: **Roy** (with Ate Noemi), cutoff before the R1 deploy.
5. **Apply** the reviewed file only, in transactions per family, through `RegisterFamilyOperation` semantics
   (imported users unverified). `registry_import_mappings` gives stable identity.
6. **One-off** (Roy D-R1, 2026-10-15): the sheet is loaded once, through the seeder/fixture. After that the app is
   the source of truth — corrections are made in the app, never synced back to the sheet. A rerun skips rows
   already loaded (safe to run twice); it does not update them.

The source CSV/XLSX never enters git (personal data) — `storage/imports/` is git-ignored. Tests: identical rerun (skips),
corrected row, reordered export, regrouped family, interrupted apply, apply racing a live registration.

---

## 8. Kept for the offline round

Search in the browser over props; check-in identity `(event_id, child_id)`; `insertOrIgnore` (re-check-in after
undo revives the row); per-child outcome map; one check-in Feature with redirect + flash, no JSON sibling, flash
prop arrival tested. The later round adds payload audit, IndexedDB queue, "Sync now", time bounds, service worker
(PoC plan §6/§4.10).

---

## 9. Hosting (D2)

Laravel Cloud; attach MySQL/Redis/Reverb/object storage/queue before the first deploy; `migrate --force`;
scheduler (event generation); trusted proxies; collation `utf8mb4_0900_ai_ci`; private bucket for photos.

---

## 10. Quality gate

`make check` = pint-test, larastan L10, pest --parallel, vitest (+ file count), eslint, vue-tsc, prettier-check,
discipline, build, migrate-check. `make e2e` before each release (mobile-webkit, mobile-chromium,
desktop-chromium). Arch tests: Lucid boundaries, DataObject, Enum, Migration, MorphMap, PhpStyle, ClassLength,
TestCoverage, Reachability, Tenancy, **every Feature declares its access-matrix row**, Flash contract, scan-key
parity. Guards strip comments, exclude tests, and are each proven to fail once.

**Built so far (review 3, 2026-10-16):** Lucid boundaries (`FeatureBoundariesTest`, `JobBoundariesTest`),
Reachability (basename search — see TODO), Tenancy (`Model::query()` statements only — see TODO), access-matrix row
(`AccessRowTest`: Org, Family, Auth photo), Flash contract, Inertia pages; component discipline in Vitest.
**Not built yet:** DataObject, Enum, Migration, MorphMap, PhpStyle, ClassLength, TestCoverage guards; scan-key
parity (Release 2, with cards). Listed in TODO #17 — the list above is the target, not the current state.

---

## 11. Reuse ledger and complexity delta

Repo is greenfield; analogues are sibling repos: aegis (`~/projects/work/aegis/aegis/core`), palo
(`~/projects/personal/current/palo`), PoC (`~/projects/personal/poc/nxtgen-attendance`).

| Needed behaviour | Nearest analogue | Decision | Evidence |
|---|---|---|---|
| Lucid bases, arch tests | aegis `Foundation/Jobs/BaseJob`, `tests/Architecture/*` | reuse (port subset) | house standard |
| Org resolution + tenancy guard | palo `SetActiveClub`, `TenancyArchTest` | extend | split into resolve + per-group authorization |
| Scoped roles | palo `ClubMembership` + `UserRoleEnum`; PoC `canWorkTheDoor()` | extend | adds scope_type + grant ceiling |
| Password login | aegis `LoginFeature` + `AuthenticateUserJob` | reuse (port) | staff backup |
| Magic links / verify / claim | none in siblings | new | no passwordless auth exists |
| Landing | PoC `ShowLandingFeature` | extend | family/staff/both/multi-org |
| Family/guardian/child | PoC models + `CreateFamilyJob`, `CreateKidJob`, `UpsertGuardianJob` | extend | fields, org scope, guardian↔user |
| Groups | PoC `LevelEnum`/`SquadEnum` | replace | per-org data; enums not ported |
| Duplicate candidates | PoC search normaliser (`useKidSearch` normalise) + palo `PhoneticSearchJob` (candidate search) | extend | candidates only, never identity |
| CSV parsing | aegis `ParseCsvRowsJob` (streamed) | extend | palo `ParseRosterCsvJob`/`DedupeRosterRowsJob` rejected: newline split, email-collapse drops siblings |
| Registry propose/review/apply | none | new | real import with human review; one pipeline |
| Photo | aegis `UploadAvatarInput` / `GenerateAvatarVariantsJob` / `DeleteUserAvatarJob` | extend | private disk + child authorization |
| Door search | PoC `useKidSearch.ts` | reuse (port) | one org-wide index; no server matcher |
| Check-in / undo | PoC `CheckInKidJob`, `CheckInKidsFeature` (ordered locks), `UndoCheckInJob` | reuse (port) | incl. lock order |
| Scan resolution | PoC `ScanCardFeature` + `useQrScanner` | extend | Code 39 name keys, zxing |
| Realtime | aegis `useEcho`, `usePrivateChannel`, `useJobOrderRealtime` | reuse (port) | |
| Flash → toast, enum SSoT | aegis `useFlashToasts`, `useToastStore`, `ProvidesEnumMetadata` | reuse (port) | |
| Excel export | none in production (aegis uses OpenSpout readers) | new | writer over scoped report queries |
| E2E harness | aegis `E2ETestingServiceProvider` | reuse (port) | |
| Discipline test | palo `component-discipline.test.ts` | reuse (port) | no stories |
| Notes | none | new | user decision: one polymorphic table; targets family (R1 system audit) and child (R2 observations) |
| Pending family claims, duplicate candidates | none | new | needed by §4.2 outcomes; no sibling has claims or review queues |
| Event series generation | PoC one-off sessions | extend | recurring + occurrence identity |
| Child status, move-up indicator | PoC `is_active`, `LevelEnum::suggestedFor()` | extend | enum status; indicator from group range |

**Complexity delta.** Added vs PoC: Organization, Branch, RoleAssignment + AccessScope, magic links,
Group (data), EventSeries, ChildStatus, Note, FamilyClaim, DuplicateCandidate, registry pipeline, staff
registration. Removed/not ported: stars,
exchange, `LevelEnum`/`SquadEnum`, temp-card pool, release, roll call, Fortify, Media Library, merge (deferred).
**Parallel paths retained: none** (door search is one client index; registration is one Operation for parent,
staff and import).

---

## 12. Schedule and exit criteria

### Release 1 — Registration (target Tue 20 Oct; ships when exit criteria pass, latest Fri 23 Oct)

| Day | Date | Work |
|---|---|---|
| 1 | Thu 15 | Scaffold, gate, arch/discipline tests, CI, empty deploy; `registry propose` started in parallel |
| 2 | Fri 16 | Orgs/branches/groups, users, role assignments + AccessScope + matrix tests skeleton, magic links + landing |
| 3 | Sat 17 – Sun 18 | Families/guardians/children schema + invariants; `RegisterFamilyOperation` (§4.2) + concurrency tests; review file to Roy |
| 4 | Mon 19 | Parent registration UI, staff registration (search, add child, resend, correct contact), Family page; Roy finishes review (cutoff) |
| 5 | Tue 20 | Admin registration view + export; `registry apply` on staging; e2e on phones; deploy if exit criteria pass |

**R1 exit criteria:** a parent registers on a phone and confirms; a staff member registers a family with an
unconfirmed email and resends; a returning imported parent is told "already registered"; matrix tests green for
every R1 Feature; projection tests green; import reconciliation balances; `make check` + `make e2e` green.

**Status (2026-10-16):**

| Criterion | Status |
|---|---|
| A parent registers on a phone and confirms | ✅ E2E `parent/register.spec.ts` (iPhone Safari, Android Chrome, desktop) |
| Staff register a family with an unconfirmed email and resend | ✅ E2E `staff/family-admin.spec.ts` |
| A returning imported parent is told "already registered" | ✅ E2E `parent/returning.spec.ts` |
| Matrix tests green for every R1 Feature (7 actor kinds) | ⏳ rows tested; full grid = **TODO #18** |
| Projection tests green | ⏳ **TODO #18** (response allowlists per actor) |
| Import reconciliation balances | ✅ in tests; ⏳ on staging with the real sheet (**TODO #21**) |
| `make check` + `make e2e` green | ✅ 310 Pest (incl. 31 arch), 106 Vitest, PHPStan L10; 43 E2E runs |

### Release 2 — Check-in (Wed 28 Oct)

| Day | Date | Work |
|---|---|---|
| 6 | Wed 21 | Event series, generation, open/close, Door search index + detail + check-in/undo + lock protocol tests |
| 7 | Thu 22 | Scan spike (real cards) → scanning; realtime |
| 8 | Fri 23 | Reports + Excel; notes; e2e |
| — | Sat 24 | Dry run (paper backup) if R2 exit criteria pass on staging |
| 9–10 | Mon 26 – Wed 28 | Fixes, buffer, deploy |

**R2 exit criteria:** family check-in with mixed outcomes; check-in vs close race test; real card resolves on
Android + iPhone; reports export with allowlisted fields; `make check` + `make e2e` green.

**If R1 uses its extension (to Fri 23 Oct)**, R2 is re-forecast the same day: dry run moves to Sat 31 Oct and R2's
exit date is restated, never silently compressed.

**If R1 slips, cut:** Excel export → staff "correct contact" (resend only) → Family page edit (view + add child
stays). **Never cut:** registration outcomes, magic-link security, scope matrix, import review.

---

## 13. Open questions (Roy)

1. ~~D1–D7~~ — picks taken ("start the development"); D2 confirmed Laravel Cloud (2026-10-16).
2. `lifeshapers_can_open` default (may Lifeshapers open a class)? — **open, needed for Release 2** (seeded `true`).
3. Follow-up threshold (weeks absent)? — **open, needed for Release 2** reports.
4. Repo name + production domain — Roy is setting up Laravel Cloud (TODO #23).
5. A "No group" filter on the children list (children outside every age band or with a mistyped birthday)? —
   **asked 2026-10-16, awaiting Roy.**
