# NxtGen v1 — Tech spec

**Status:** DRAFT v3 for Roy's review · **Written:** 2026-10-14 · **Revised:** 2026-10-14 (v2: Ate Noemi's
feedback; v3: Roy's corrections) · **Target:** first release 2026-10-28
**Product doc (stakeholders, ELI5):** https://claude.ai/code/artifact/4dcab16b-b96d-42a7-b08f-aa4f189c41e0
(PDF copy in repo root) · **Raw requirements:** `project.md`

**v3 in one line:** online first (offline + payload work is a later round), area is a text field (map later),
no walk-in shortcut — every child is registered, by the parent or by staff with an unconfirmed email, families
have a home satellite, admin roles are scoped church / satellite / class, door search covers the whole church.

---

## 0. Decisions

### Still open (recommended option first)

| # | Decision | Recommended | Alternative | Affects |
|---|---|---|---|---|
| D1 | Database | **MySQL 8.4** — palo + PoC run it on Laravel Cloud; PoC's generated-column invariants verified on it | Postgres 16 (aegis) | §5 |
| D2 | Hosting | **Laravel Cloud** — TLS (camera), managed MySQL/Redis/queue/Reverb/object storage, `git push` deploy | Hetzner + Compose (aegis) | §9 |
| D3 | WebSockets | **Reverb** (Pusher protocol → aegis `useEcho` ports as-is) | Soketi (aegis) | §7.6 |
| D4 | Realtime | **Event = cue to refetch** (aegis) + 30 s polling fallback; never load-bearing | Polling only (palo) | §7.6 |
| D5 | PHPStan | **Level 10 from day one** | Level 8 (aegis/palo) | gate |
| D6 | Storybook | **No** — `.test.ts` siblings only | Storybook 10 (aegis) | discipline |
| D7 | Staff login | **Password + magic link** for staff; magic link only for parents | Magic link only | §6 |

### Settled

| Topic | Settled as |
|---|---|
| Naming | Code `Branch`, UI **"Satellite"** (per-org label). House Church and Holy Trinity Church are satellites |
| Family ↔ satellite | A family belongs to the **org** and has a **home satellite** (`home_branch_id`) |
| Visibility | Staff manage only their scope. **Door search covers the whole org** (prevents double registration — same umbrella); kids from another satellite show a minimal projection |
| Admin roles | Scoped: **church admin / satellite admin / class admin**; a user can hold several. Merge = church admin only |
| Registration | Every child is registered before check-in. Parent self-registration preferred. **Staff can register** (search first), email required, **unconfirmed** until the parent taps the link; unconfirmed never blocks check-in; badge + resend/fix next visit. No walk-in shortcut |
| Offline | **Later** — one "payload + offline" round after v1. v1 keeps the PoC invariants so it stays additive |
| Area | **Text input** in v1. Global area list + Google map picker + "nearby DGroups" later |
| Child status | `active` / `inactive` / `graduated` (finished NxtGen 5–12 → Elevate). Duplicates are **merged**, not flagged |
| Classes | Named repeating events per satellite ("1PM", "7PM" …); times are data |
| Reports | In-app lists + **Excel export**. No Sheets sync |
| Cards | Today's Code 39 cards until Moving-Up (Feb), then QR + reprint. First card free, replacement **AED 10**. Printable layout in v1 |
| Existing data | Seeder from the Google Sheet |
| Later | Offline · payload optimisation · area map · kiosk · roll call · head count · pickup · Squad Leader notes · parent attendance/notes pages · QR printing run · Sheets sync |

---

## 1. Stack

| Layer | Choice | Source |
|---|---|---|
| PHP | 8.4 | aegis |
| Framework | **Laravel 12** (NOT 13 — Lucid v2 silently downgrades to v1.0.11 on 13) | PoC trap |
| Architecture | `lucidarch/lucid ^2.0` — Controller → Feature → Job; Operation only where domains cross | aegis |
| DTOs | `spatie/laravel-data ^4` — `*Input` in `Services/*/Data`, `*Data` (`#[TypeScript]`, `fromModel()`) in `Domains/*/DataObjects` | aegis |
| TS types | `spatie/laravel-typescript-transformer` → `resources/js/types/generated.d.ts` | aegis/palo |
| Frontend | Inertia 3 · Vue 3.5 · TS strict · Pinia · Reka UI 2 · Tailwind 4 · lucide · Ziggy `route()` | aegis/palo |
| Media | `spatie/laravel-medialibrary` — child photo only, private, signed URLs, object storage | new |
| Realtime | Reverb + `laravel-echo` + `pusher-js` | D3 |
| Scanning | `@zxing/browser` (Code 39 + QR); native `BarcodeDetector` first where available | new |
| Excel | `openspout/openspout` — streamed `.xlsx` | aegis |
| Codes | `bacon/bacon-qr-code` (QR, Feb) + a Code 39 renderer for the print layout | PoC |
| Mail | Resend | palo |
| Errors | Sentry | aegis |
| Tests | Pest 4 (+arch) · Vitest + happy-dom + @vue/test-utils · Playwright | aegis/palo |
| Quality | Pint · Larastan L10 · ESLint 9 flat · Prettier (+tailwind) · vue-tsc | aegis |
| Local dev | Compose: `app`, `node`, `mysql:8.4`, `redis:7`, `reverb`, `mailpit` | aegis-style |
| Later | `idb` + `vite-plugin-pwa` (offline) · `@googlemaps/js-api-loader` + `pragmarx/countries` (areas) | — |

---

## 2. Repo layout

```
app/
  Data/Models/              Eloquent only
  Domains/{Domain}/         Jobs/, DataObjects/, Tests/       ← business logic
  Operations/{Area}/        only where domains cross
  Services/{Service}/       Data/ (Inputs), Features/, Http/{Controllers,Middleware}, Providers/, routes/, STORIES.md
  Foundation/               BaseJob, attributes, concerns, values
  Enums/                    *Enum with HasEnumMetadata + toFrontend()
  Events/ Listeners/ Broadcasting/ Notifications/ Console/Commands/
resources/js/
  pages/{Public,Auth,Door,Admin,Family,Platform}/
  components/{atoms,molecules,organisms,layout}/ + REGISTRY.md
  composables/ stores/ types/ tests/
resources/views/cards/      Blade print sheets (the only Blade documents)
.planning/  STATE.md  TODO.md  tasks/<date>-<slug>/
docs/DECISIONS.md
```

### Services

| Service | URL | Who | Device |
|---|---|---|---|
| `Public` | `/`, `/{org}/register`, `/verify-email/{token}` | parents | phone |
| `Auth` | `/login`, `/magic/{token}` | everyone | any |
| `Door` | `/{org}/{branch}/events/{event}/…` | Lifeshaper (+admins) | phone |
| `Admin` | `/{org}/admin/…` | scoped admins (+ Lifeshapers for registration) | laptop / phone |
| `Family` | `/{org}/family/…` | parent | phone |
| `Platform` | `/platform/…` | Roy | laptop |

### Domains

`Organization` (orgs, branches, role assignments) · `User` · `Auth` · `Family` · `Child` · `Group` · `Event` ·
`Attendance` · `Card` · `Note` · `Merge` · `Report` · `Import`.

Operations: `RegisterFamilyOperation` (Family + Child + Auth), `AddChildToFamilyOperation`,
`MergeChildrenOperation` / `MergeFamiliesOperation`, `ApproveCardRequestOperation`. **Check-in is never an
Operation** (keeps offline replay possible later).

---

## 3. Tenancy and scope

Org = tenant. palo-style: URL-resolved, explicit `organization_id`, arch-test enforced, no global scopes.

- Tenant tables carry `organization_id`; branch-owned add `branch_id`.
- **Org-owned:** families (with `home_branch_id`), children, groups, cards, card requests, notes.
  **Branch-owned:** event series, events, attendance. **Global:** users, magic links.
- `SetActiveOrganization` middleware resolves `{org:slug}` → org + the user's role assignments on the request;
  **404 for non-members**; not session-based. Nested routes use `->scopeBindings()`.
- `TenancyArchTest`: every tenant query mentions `organization_id`; every model is tenant-listed or global-listed.

### Scope rules (one place: `AccessScope` value built from the user's role assignments)

| Scope | Manages | Sees families / children |
|---|---|---|
| Church admin | everything in the org | all |
| Satellite admin | that branch's classes, events, attendance, volunteers, and families whose `home_branch_id` is that branch | home-branch families fully |
| Class admin | that event series' events + attendance | children who attend that class |
| Lifeshaper | door work in their branches; can register families | home-branch families; others via door search |

- **Door search is org-wide.** Kids whose home satellite is another branch come back as a **minimal projection**
  (`ChildDoorLiteData`: name, nickname, allergies, group, home satellite name) — enough to check in or to see
  "already registered", nothing more. Checking them in records attendance on the current branch.
- Every Admin list query goes through one `ScopeQueryJob`-style filter so a page can't forget the scope; Feature
  tests per scope (church/satellite/class) for every list and every mutation.

---

## 4. Users and roles

```
users             id, name, email (unique), email_verified_at?, password (nullable), is_platform_admin, last_login_at
role_assignments  id, organization_id, user_id, role (RoleEnum: admin | lifeshaper),
                  scope_type (organization | branch | event_series), scope_id, is_active
                  UNIQUE(user_id, role, scope_type, scope_id)
families.primary_user_id → users.id                           ← the family email
guardians.user_id nullable                                    ← 2nd parent login, later
```

- Parent capability = being a family's `primary_user_id` (not a role row).
- `RoleEnum` methods own the hierarchy (`canWorkTheDoor()`, `canManage()`); scope checks live in `AccessScope`.
  No spatie/permission.
- Squad Leader (later) = lifeshaper assignment scoped to a group.
- Change family email (admins in scope): updates the user's email, clears `email_verified_at`, sends a new link.
- Health fields visible to staff of the org and the child's own family only.

---

## 5. Data model

`timestampTz`; every column `->comment()`; create-migrations editable until the first deploy holds real data.

### 5.1 Core

```
organizations   id, name, slug, timezone, currency, settings json
                (satellite_label, replacement_card_fee_minor=1000, first_card_free=true, printing_paused_until?)
branches        id, organization_id, name ("Holy Trinity Church", "House Church"), slug
groups          id, organization_id, name, sort, colour_token, min_age?, max_age?, is_active

families        id, organization_id, home_branch_id, primary_user_id, area_text?, fb_account, wants_fb_group,
                in_dgroup, dgroup_area_text?, dgroup_leader_name, dgroup_leader_phone, wants_dgroup,
                ministries json, lifestage?, preferred_event_series_ids json, privacy_consented_at?,
                registered_by_user_id?, registration_source (self | staff | import), notes
guardians       id, family_id, user_id?, relationship (father|mother|guardian|grandparent|aunt_uncle|sibling|other),
                first_name, last_name, phone, email?, ministries json, can_pick_up bool

children        id, organization_id, family_id, group_id?, first_name, middle_name?, last_name, nickname,
                gender, date_of_birth, allergies, illnesses, dislikes, fears, parent_notes,
                status (ChildStatusEnum: active|inactive|graduated), status_changed_at, status_changed_by,
                photo_consent_at?, photo_consent_by?, registration_source, merged_into_id?
                display_name = nickname ?? first_name (accessor)
```

"Email not confirmed" = `users.email_verified_at IS NULL` for the family's primary user (derived, not stored on
the family).

### 5.2 Classes and attendance

```
event_series    id, organization_id, branch_id, name, weekday, starts_at, ends_at, is_active
events          id, organization_id, branch_id, event_series_id?, name, starts_at, ends_at,
                opened_at?, opened_by?, closed_at?, closed_by?          ← never named `sessions`
attendances     id, organization_id, event_id, child_id  UNIQUE(event_id, child_id)
                checked_in_at, checked_in_by, undone_at?, undone_by?,
                method_hint? (scan|search — analytics only)
```

`tapped_at` / `synced_at` arrive with the offline round as a dated `add_*` migration.

### 5.3 Cards

```
id_cards          id, organization_id, child_id?, type (permanent|temporary), symbology (code39|qr),
                  code (QR: 8-char random A–Z0–9, UNIQUE per org; legacy code39: NULL), serial,
                  state (pending_print|usable|lost|retired), printed_at?, retired_at?
                  + generated live_permanent_child_id UNIQUE
card_scan_events  id, organization_id, id_card_id?, child_id?, raw_value, scanned_by, scanned_at,
                  outcome (resolved|ambiguous|unknown|refused)
card_requests     id, organization_id, child_id, requested_by, reason (lost|damaged|other),
                  status (requested|approved|rejected|printed|handed_over), fee_minor, fee_currency,
                  paid_at?, paid_confirmed_by?, approved_by?, old_card_id?, new_card_id?
                  + generated open_request_child_id UNIQUE
```

**Legacy name barcodes.** Code 39, caps, no spaces; printed cards hold **NICKNAME+SURNAME**, the sheet column the
full name. `ChildScanKeys` = `[norm(nickname+last), norm(first+middle+last), norm(first+last)]`,
`norm` = strip diacritics → uppercase → A–Z0–9. Scan → strip `*` → `norm` → match. One hit = show child;
several = pick list; none = search pre-filled. Same rule in TS and PHP with a shared parity fixture.

**QR (Feb).** 8-char random code, ECC Q → version 1 → print **~2 cm**; test sheet 1.5 / 2 / 2.5 cm first.

### 5.4 Notes, merges, auth tokens, media

```
notes           id, organization_id, noteable_type, noteable_id (morph-mapped), author_id, body,
                visibility (staff|family), recipient_user_id? (later)
merges          id, organization_id, subject_type (child|family), kept_id, merged_id, field_choices json,
                snapshot json, merged_by, merged_at
magic_links     id, user_id, purpose (login|verify_email), token_hash, expires_at, used_at?, ip, user_agent
media           spatie table (child `photo`, consent required)
```

### 5.5 Database-enforced invariants

| Invariant | How |
|---|---|
| One check-in per child per event | `UNIQUE(event_id, child_id)` + `insertOrIgnore` |
| One live permanent card per child | generated `live_permanent_child_id` + UNIQUE |
| One open card request per child | generated `open_request_child_id` + UNIQUE |
| QR code unique per org | `UNIQUE(organization_id, code)` |
| One role assignment per scope | `UNIQUE(user_id, role, scope_type, scope_id)` |

FKs feeding generated columns are `RESTRICT`; `code` uses `ascii_bin`.

---

## 6. Auth

- **Magic link:** 32 random bytes, SHA-256 stored, single use, throttled per email + IP, identical response
  whether or not the email exists. `purpose=login` (15 min) or `purpose=verify_email` (7 days — the parent may
  only check email at home).
- Verifying sets `email_verified_at` and logs the parent in.
- **Password (staff, D7):** hand-rolled Lucid login (aegis), `throttle:login`, reset flow.
- Redis sessions, remember-me on.
- E2E: per-project accounts + `auth.setup.ts`; `/e2e/last-magic-link` under `APP_ENV=e2e` only.

---

## 7. Features

### 7.1 Registration — parent (Public, online)
- `/{org}/register` (+ printable QR poster). Fields per the product doc; **area as text**; home satellite;
  preferred classes; photo + consent; privacy consent.
- Duplicate check (`FindPossibleDuplicatesJob`: same email, same guardian phone, same child first+last+DOB)
  → "looks like you're already registered — ask a Lifeshaper" instead of creating.
- Creates user (no password) + family + guardians + children (`active`, group suggested by age) and sends a
  `verify_email` link (doubles as their login).

### 7.2 Registration — staff (Admin/Door, online)
- **Search first** (`SearchFamiliesJob`: parent name, phone, email fragment, child name; fuzzy, org-wide,
  scoped projection). Results show the masked family email ("which email did we use?").
- Found → **add child** to that family. Not found → register family + child; **email required**; family's user
  created unverified; `verify_email` link sent; `registration_source=staff`, `registered_by_user_id` set.
- Child is **checkable immediately**; unconfirmed never blocks the door.
- Family card shows an **"email not confirmed"** badge with **Resend** and **Fix email** (fixing re-sends).

### 7.3 Door (Lifeshaper, phone)
- Satellite → today's classes → open class (who may open: open question).
- Roster in props: home-branch active children full projection (`ChildDoorRowData`); search reaches the rest of
  the org through a server search endpoint returning `ChildDoorLiteData`.
  *(v1 is online, so an org-wide search request is fine; the offline round will revisit what is cached.)*
- Client-side search over the roster (`useChildSearch`, PoC algorithm), filters by group / in / not in, family
  grouping, allergy badge.
- Scan (§5.3) → show the child → **tap to check in**. Check in one child or the family (per-child outcome map);
  undo one child.
- "Not registered?" button → §7.2 flow.
- Add a note to a child. Legibility floor (PoC §5.10) on `[data-surface="door"]`.

### 7.4 Admin (scoped dashboard)
- Families: list/search, view, edit, change email, resend confirmation, **merge** (church admin).
- Children: list/filter (status, group, satellite), profile (details, attendance, notes, cards, photo), edit, move
  group, **set status**, **merge** (church admin).
- Groups CRUD + order (church admin).
- Satellites (church admin) · classes + one-off events (church/satellite admin) · open/close.
- **Reports** (Excel export on each): attendance per class, child history, follow-up list (active kids absent
  N weeks), moving-up list.
- Cards: requests (approve/reject, mark paid, AED 10 unless first card), **print sheet** (Blade, A4, CR80
  85.6×54 mm, NxtGen layout: big nickname, full name, Code 39 now / QR later, 10 per page), QR size test sheet.
- People: invite staff (magic link), assign roles + scopes, deactivate.

### 7.5 Family (parent, phone)
- **v1 must:** view family, add a child, edit details, request a replacement card (shows fee).
- Later: attendance history, `family` notes, leave notes, Squad Leader messages.

### 7.6 Realtime
- Private channels `org.{id}.event.{id}`; `AttendanceChanged`, `EventOpened`, `EventClosed`
  (`ShouldBroadcastNow`, tiny payload). aegis `useEcho` + `usePrivateChannel` + debounced
  `router.reload({ only })`; 30 s polling fallback.

### 7.7 Seeder (existing Google Sheet)
- `RegistrySeeder` (or `nxtgen:import-registry {csv}`) from the **Registry** tab export (289 rows, 195 UNIQUE).
- Skip `DUPLICATE`; `FORWARDED TO ELEVATE…` → `graduated`; `WENT HOME…` / `remove…` → `inactive`; else `active`.
- Families grouped by normalised parent email (fallback phone last 9 digits) → ~200 families; imported users
  are **verified=false** until they log in once.
- Home satellite from "Attending worship schedule" (3PM/7PM → CCF DXB; House Church → House Church;
  "Either …" → CCF DXB, flagged for review). Area copied as text.
- Dry run prints counts + problems; idempotent re-run keyed on a source-row hash.

---

## 8. Kept for the later offline round

v1 is online-only, but keeps the PoC invariants so offline is an addition:
1. Search runs in the browser over the roster in props.
2. Check-in identity is `(event_id, child_id)` only.
3. `insertOrIgnore`; re-check-in after undo revives the same row.
4. One row per child; family tap returns a per-child outcome map.
5. One check-in Feature, redirect + flash, no JSON sibling endpoint; test that the flash prop arrives.

The later round: payload audit (gzip at the edge, roster versioning/ETag), IndexedDB queue + "Sync now",
three-state queue, time bounds, service worker — the full PoC §6/§4.10 design.

---

## 9. Hosting (D2 = Laravel Cloud)

Attach MySQL, Redis, Reverb, object storage, queue **before** the first deploy; deploy runs
`php artisan migrate --force`; scheduler generates events; trusted proxies; collation `utf8mb4_0900_ai_ci`.

---

## 10. Quality gate (`make check`)

```
check: pint-test analyse test-parallel test-fe lint typecheck prettier-check discipline build migrate-check
```

Larastan L10; Pest `--parallel`; Vitest + file count; `migrate:fresh --env=testing`; `make e2e` before shipping
(mobile-webkit, mobile-chromium, desktop-chromium). Arch tests: Lucid boundaries, DataObject, Enum, Migration,
MorphMap, PhpStyle, ClassLength, TestCoverage, Reachability, **Tenancy**, **Scope coverage** (every Admin
Feature declares its scope rule), Flash contract, scan-key parity. Frontend discipline as aegis minus stories.

---

## 11. Build sequence

Dry run **Saturday 24 Oct**, release **Wednesday 28 Oct**.

| Day | Date | Work | Done when |
|---|---|---|---|
| 0 | Thu 15 (1 h) | **Scan spike:** real cards × Android + iPhone × (`BarcodeDetector`, `@zxing/browser`) | phones read the cards, or we buy a AED 199 Bluetooth scanner |
| 1 | Thu 15 | Scaffold, gate, arch/discipline tests, CI, empty deploy | gate green; page live |
| 2 | Fri 16 | Tenancy, users, **scoped role assignments**, magic link + password, Platform create org, seed CCF + satellites | each scope sees only what it should (tests) |
| 3 | Mon 19 | Families, guardians, children, groups; staff registration (search first, unconfirmed email, resend/fix) | a volunteer registers a family on a phone |
| 4 | Tue 20 | Parent registration + email verification + **Registry seeder** | real kids loaded; a parent registers and confirms |
| 5 | Wed 21 | Satellites, classes, event generation, open/close, Door list/search (org-wide lite), check-in/undo | a family is checked in at "1PM" |
| 6 | Thu 22 | Scanning (legacy keys) + realtime + reports + Excel | a real card checks a child in; follow-up list exports |
| 7 | Fri 23 | Child status + merge + notes; deploy for the dry run | merge works end to end |
| — | **Sat 24** | **Dry run**, paper backup | real attendance captured |
| 8 | Mon 26 | Dry-run fixes · card requests + fee | — |
| 9 | Tue 27 | Print sheet layout · people admin · Family page | — |
| 10 | Wed 28 | Buffer, docs, handover | **v1** |

**If it slips, cut in this order:** realtime (polling) → print sheet → card requests → Family page → merge
(manual DB fix) → notes. **Never cut:** tenancy + scopes, registration (parent + staff), seeder, check-in
(search + scan), reports export.

---

## 12. Open questions

**Product:** AED 5 + two-tablet setup (meeting); which name the printed barcode holds (we accept both); per-child
past attendance; kiosk now or after Feb.

**Engineering (Roy):**
1. D1–D7 (or "go with your picks").
2. May a Lifeshaper open a class, or only admins?
3. Can satellite/class admins **edit** child details, or only view? (Spec assumes satellite admin edits its
   home-branch families; class admin views.)
4. Follow-up threshold (weeks absent)?
5. Repo name + production domain.
6. `/plan-spar` with Codex first, or straight to the scan spike + scaffold?
