# Review — NxtGen Release 1 (registration)

**Status:** decisions taken (D-R1, D-R2 — 2026-10-15) · **Date:** 2026-10-15 · **Reviewed:** uncommitted working tree on
`release-1` (on top of `e045817`) against `plan.md` §3–§7, §12 and Roy's later decisions

Intent sources found: `plan.md` (approved), `spar.md` (plan rounds + 6 diff-review rounds), `.planning/STATE.md`,
`.planning/SECURITY.md`, `.planning/TODO.md`, Roy's chat decisions (login links 2 h; v1 email-correction rule;
security list must not block; TDD). **No decisions log** — `docs/DECISIONS.md` (plan §2) was never created.

## Intent ledger

| # | Commitment | Source | Status | Evidence |
|---|---|---|---|---|
| 1 | Parent registration at `/{org}/register` with today's Google Form fields + home satellite | plan §6 R1 | **PARTIAL** | Form lacks lifestage, ministries, DGroup area / leader phone (in data, not in UI) |
| 2 | Required fields: child name, nickname, gender, birthday, allergies y/n; parent name, phone, email, satellite, consent | plan §6 R1 | PARTIAL | All required except "allergies yes/no" (free text only); nickname optional (defaults to first name) |
| 3 | Printable QR poster for the registration link | plan §6 R1 | **MISSING** | no poster route |
| 4 | Four registration outcomes via one Operation, under a lock | plan §4.2 | DONE | `RegisterFamilyOperationTest` |
| 5 | Pending claims: one per email per church; repeat registration re-sends | plan §4.2, spar r2 | DONE | schema + operation tests |
| 6 | Duplicate candidates recorded, never auto-merged | plan §4.2 | DONE | operation test |
| 7 | Staff **review** of duplicate candidates (filter, dismiss/confirm) | plan §6 R1 | **MISSING** | no screen; candidates can only accumulate |
| 8 | Magic links: hashed, single use (atomic), bound to email + version / claim revision; login 2 h, verify/claim 7 d | plan §4.3, Roy | DONE | Auth job tests; revert-check A passes |
| 9 | Email scanners cannot spend links (GET → Continue, POST uses) | diff r3 | DONE | revert-check I |
| 10 | Same answer for unknown emails on /login; "register here" email | plan §4.3 | DONE (refined) | sign-in tests; the email says "ask a Lifeshaper for your church's link" — /login has no church context (review B3 rejected) |
| 11 | Rate limits: login per email + IP; registration per IP; recipient-level link limit; staff told honestly when a send was suppressed | SECURITY #7, diff r4, review A/B | DONE | revert-checks D, R2; correction path test |
| 12 | Staff password backup login + reset (D7) | plan §0 D7, §6 R1 | **MISSING** | no password route; seeder sets passwords nobody can use |
| 13 | Landing: none / one / both / several churches | plan §4.5 | DONE | `LandingTest` |
| 14 | Landing choice **remembered**, switchable from the menu | plan §4.5 | **MISSING** | chooser every time; no switch in AdminShell / Family page |
| 15 | Family page: view, add child, **edit details** | plan §6 R1 | PARTIAL | edit route + tests exist, **no edit UI** |
| 16 | Admin lists: search + filters (status, group, satellite, unconfirmed, duplicates) | plan §6 R1 | PARTIAL | only "unconfirmed" filter; no search on the list |
| 17 | Admin view/**edit** family + child details within scope | plan §6 R1 | PARTIAL | view yes; edit only status + email |
| 18 | Family **system notes** (contact correction audited as a note) | plan §4.4, §5 | **MISSING** | `notes` table never written; no Note model; no morph map |
| 19 | Set child status (church admin / satellite admin of home branch) | plan §3.2 | DONE | `AdminCorrectionsTest` |
| 20 | Correct contact — v1 rule (family-only, unverified, not staff), locked + rechecked; honest when the link is not sent | plan §4.4, Roy, diff r1, review r2 | DONE | tests; revert-check G |
| 21 | Excel export of the registration list, allowlisted, text-only | plan §6, diff r5 | DONE | revert-check B |
| 22 | **Platform**: create church + first admin; satellites, groups, people; invite staff; role-grant ceiling | plan §6 R1, SECURITY #9 | **MISSING** | only `DemoChurchSeeder`; no way to add a Lifeshaper |
| 23 | Access matrix enforced by AccessScope, tested per actor (anon, parent, staff, parent+staff, revoked, other branch, other org) | plan §3.2 | PARTIAL | resend now scoped (review A, revert-check R1); parent+staff and revoked-staff HTTP cases still missing |
| 24 | Arch test: every Admin/Door Feature declares its matrix row | plan §3.2, §10 | **MISSING** | not in `tests/Architecture` |
| 25 | `TenancyArchTest` (palo port) | plan §3.1, §10 | **MISSING** | not ported |
| 26 | Projections (§3.3) + response tests that forbidden fields are absent | plan §3.3 | PARTIAL | only search hides phone (one `missing()` assertion) |
| 27 | Photo upload + consent; private signed URLs | plan §6 R1, §1 | **MISSING** | `photo_path` column only |
| 28 | Sentry with PII scrubber | plan §1 | **MISSING** | not installed |
| 29 | Group rule: age on reference date + gender → one group, else unassigned; reviewed import groups kept | plan §5 | DONE | operation tests; revert-check R7 |
| 30 | Group ranges validated non-overlapping; next group same org | plan §5 | MISSING | no group management yet (seeder only) |
| 31 | Registry import: one pipeline, review file, no emails, idempotent; every record accounted for; partial apply fails; edited rows re-validated | plan §7, review B2/r2/r3 | DONE | import tests; revert-checks R5, R6 |
| 32 | Phone matches are suggestions only | plan §7, spar r1 | DONE | proposal test |
| 33 | `source_child_key` from Form timestamp + submitter email | plan §7 | REVERSED (now recorded) | built as email + child first name + birthday (Registry tab has no timestamp); plan §7 corrected; name+birthday guard + Distinct-child override cover corrected rows |
| 34 | Rerun updates fields not edited in-app since import; conflicts reported | plan §7 | **MISSING → decision D-R1** | rerun skips mapped rows |
| 35 | Corrected rows never imported twice; distinct-child override | diff r3–r4 | DONE | import tests |
| 36 | R1 exit: "returning imported parent is told already registered" | plan §12 | **REVERSED (recorded)** | diff r6 made the public answer identical; the specific message now goes only by email |
| 37 | R1 exit: `make e2e` green | plan §12, TODO #2 | **MISSING** | no Playwright specs; Prettier's e2e glob silenced with `--no-error-on-unmatched-pattern` |
| 38 | `make check` never touches the working DB | STATE | DONE | `.env.testing`; verified user count survives |
| 39 | CI running the gate | plan §10 | PARTIAL | adapted (PHP 8.4, ports); never run; e2e job parked until specs exist |
| 40 | Decisions log `docs/DECISIONS.md` | plan §2 | MISSING | — |

**Final tally:** DONE 17 · UNTESTED 1 (claim recheck, concurrency-only) · PARTIAL 9 · MISSING 12 · REVERSED 2 (both now recorded) · DOCUMENTED-ONLY 1 found and fixed ("link sent" when it was suppressed — Resend and email correction)

Note — the claim-acceptance recheck under lock (diff r5) is implemented but **survives its own revert**: the
sequential test cannot reach the interleaving it guards (revert-check H). It is effectively UNTESTED.

## Phase 3 sweeps

- **Dead-end states.** `family_claims.pending` has no staff exit (no revoke); a parent who lost the inbox blocks
  that email for the church forever (registration only re-sends). `duplicate_candidates.open` has no exit at all.
- **Fixtures that manufacture the impossible.** `GuardianFactory` makes a guardian with neither `user_id` nor
  `email`; `FamilyFactory` makes families without a primary guardian — no application path produces either.
- **Workarounds as evidence.** Prettier `--no-error-on-unmatched-pattern` exists only because there are no e2e
  specs; slug re-resolution in two middlewares + one Input exists because route-model binding runs after them.
- **Tests that survive their fix.** 9 fixes reverted one by one: 8 tests fail as they should; H survives.
- **Undocumented guards.** Recipient mail limit silently dropped emails over 5 / 10 min while staff screens said
  "sent" — fixed (review A/B r1–r2): both staff paths now report the suppression.

## Reuse and debt ledger

| New or changed concept | Closest existing implementation | Old path removed? | Judgment |
|---|---|---|---|
| "family email / name / confirmed" derivation | — | yes | **good refactor** — 5 copies → `Family::contactName/contactEmail/contactEmailConfirmed` |
| Organization slug → model in RequireGuardian, RequireAssignment, RegisterFamilyInput | plan's `ResolveOrganization` | yes | **good refactor** — 3 copies → `Foundation\Support\RouteOrganization` |
| Form → registration mapping in public + staff Features | — | yes | **good refactor** — 2 drifting copies → `RegisterFamilyInput::toRegistration()` |
| `FamilyListRowData` / `FamilySearchResultData` / `FamilyAdminData` / `FamilyPortalData` | each other | n/a | necessary (different field allowlists per §3.3), but share the derivation above |
| `ChildInput` / `GuardianInput` / `RegisterFamilyInput` moved to `Foundation/Inputs` | Public service inputs | yes | good refactor |
| `AccessScope` | palo membership roles | n/a | necessary addition |
| `GetFamilyChildJob` vs `GetOrganizationChildJob` | each other | n/a | necessary (family vs church scope) |
| `CreateChildJob::clean` / `UpdateChildDetailsJob::clean` / `CreateFamilyJob::clean` | each other | — | minor duplication |
| `TextRow` | — | Row::fromValues no longer used | good refactor |
| `EmailMask` | — | n/a | necessary |
| Registry pipeline (rows, proposal, review, apply) | PoC RosterSeeder (fake) | n/a | necessary |
| `stubs/Lucid.stub` + `BaseJob<T>` | — | n/a | necessary (level 10) |

**Complexity delta (vs plan §11):** added as planned — Organization, Branch, RoleAssignment + AccessScope, magic
links, Group, ChildStatus, FamilyClaim, DuplicateCandidate, registry pipeline, staff registration. Added beyond
plan — `TextRow`, `EmailMask`, `ClaimBindingFailed`, typed Lucid stub. Planned but absent — Note model, Platform,
`ResolveOrganization`. Parallel paths retained — none intended; **two found** (family-email derivation ×5, slug
resolution ×3).

**Debt tally:** necessary 7 · good refactor 4 · missed refactor 0 (after fixes) · duplicate 0 (after fixes; minor
`clean()` helper copies left) · premature 0.

## Outcome

### Fixed now (each test verified to fail with the fix reverted)

| Finding | Fix |
|---|---|
| Resend ignored the access matrix (review A1) | `AccessScope::canResendLink`, enforced before any token is issued |
| Staff told "sent" when the recipient limit suppressed the email (B3, r2) | `SendMagicLinkJob` returns bool; Resend and email correction report it |
| Already-registered login email gave no context (B3) | explanation line in the email |
| Staff registration dropped DGroup area / leader phone / lifestage (B6) | one `RegisterFamilyInput::toRegistration()` for both paths |
| Import dropped short records; reported success on partial apply; overcounted families (B2) | pad + quarantine; non-zero exit with problems; accurate counts |
| Import recalculated groups instead of keeping the reviewed one (A3) | `Group` column, validated per church, kept by `AddChildToFamilyOperation` |
| Approved rows with no family key / no Families sheet vanished (r2) | reported as problems |
| Edited rows not re-validated — blank emails merged households (r3, high) | identity fields re-validated at load |
| CI pointed at the PoC (B8) | PHP 8.4, this repo's ports; e2e job parked |
| Duplicate implementations (B non-blocking) | three consolidations above |

### Needs a decision (not built)

> **Decided by Roy, 2026-10-15.** D-R1 → (a): the sheet is loaded once via seeder/fixture; the app is the source
> of truth; no sync back (plan §7.6). D-R2 → screens: **People** built (`/{church}/people`, grant ceiling, invite
> link, remove role, last-admin guard); the church and first admin come from the seeder. Also: Admin service
> renamed **Org**, URLs `/{church}/…` with no `/admin`.

**D-R1 — Import reruns: update fields, or skip?** Plan §7 promises field-level updates with a conflict report; the
build skips already-loaded rows. *Today:* a correction made in the sheet after loading is ignored (the row is
skipped; a corrected name/birthday is flagged instead). *Options:* (a) amend the plan — the import is one-off,
corrections after loading are made in the app (recommended: no extra code, matches how the data will really be
handled); (b) build baseline + per-field update + conflict report (~1 day). *Need from you:* will the Google Sheet
keep being edited after the first load, or does the app become the source of truth? That decides it.

**D-R2 — Getting real staff into production.** There is no screen or command to create the church's first admin,
satellites or Lifeshapers; only the demo seeder. *Options:* (a) two artisan commands now (`nxtgen:create-church`,
`nxtgen:add-staff`) and the Platform/People screens after Release 1 (recommended: unblocks deploy in hours);
(b) build the screens first (~1–1.5 days, delays Release 1). *Need from you:* how many staff accounts at launch,
and who adds them — you, or church admins themselves?

### Remaining Release 1 build (accepted, unbuilt — plan scope, not decisions)

~~Family-page edit UI + guardian/family edit + full health display (B5)~~ done · ~~registration form field parity + explicit
allergy yes/no (B6)~~ done · ~~duplicate review screen, list search/filters, system notes (B7)~~ done · ~~Playwright journeys (B8)~~ done ·
~~QR poster, staff password backup, remembered landing choice (B9)~~ done · photo + consent (**needs Roy**) · ~~Sentry~~ wired, needs DSN · ~~`TenancyArchTest` +
matrix-row arch test~~ done · ~~decisions log~~ done (`docs/DECISIONS.md`).

### Logged (with trigger)

- Claim recheck under lock is untested by any sequential test — **becomes testable** when a concurrency harness
  (two DB connections) is added for check-in in Release 2; add the claim case then.
- Mixed group → per-gender next group (TODO #1) — **before Feb Moving-Up**.

---

# Review 2 — Org rename, People, E2E harness (PAUSED)

**Status:** paused at Roy's request ("start doing the e2e… hard part later") · **Date:** 2026-10-15 ·
**Reviewed:** `7fe14cd` + uncommitted E2E harness. Codex rounds NOT yet run — resume with Phase 5.

## Intent ledger (so far)

| # | Commitment | Source | Status |
|---|---|---|---|
| 1 | Admin → Org, no `/admin` in URLs | Roy chat | DONE — `OrgRoutingTest` |
| 2 | Fixed top-level paths reserved from the church wildcard | plan §2 | DONE — revert fails `OrgRoutingTest` |
| 3 | Staff register at `/{church}/families/new`; public stays `/{church}/register` | Roy chat / plan §2 | DONE |
| 4 | People: church admin sees all, satellite admin own satellite, Lifeshaper none | plan §6, D-R2 | DONE — revert-checked |
| 5 | Existing login (parent) gains the role, no second account | plan §4, D-R2 | DONE — revert fails 3 |
| 6 | Grant ceiling (add AND remove) | plan §3.2 | DONE for admin/Lifeshaper; **PARTIAL** class-admin (R2, TODO #9) |
| 7 | Invite = sign-in link naming the role (2 h) | D-R2, Roy 2 h | DONE |
| 8 | Never remove the last church admin (under lock) | plan §6 | DONE — revert-checked |
| 9 | People menu for admins only | build | DONE — revert-checked |
| 10 | Every Feature declares its matrix row (arch test); matrix rows × 7 actor kinds | plan §3.2/§10 | **MISSING** (on the R1 remaining list) |
| 11 | Church + first admin come from a seeder in R1 | plan §6 | **MISSING** — only DemoChurchSeeder (password "password", platform admin) — TODO #5 |
| 12 | Import one-off, rerun skips | plan §7.6 / D-R1 | DONE (built that way before the decision) |
| 13 | `make e2e` on mobile-webkit, **mobile-chromium**, desktop-chromium | plan §10 | **PARTIAL** — mobile-chromium dropped → fixed now |
| 14 | E2E never touches working data | build docblocks | **PARTIAL** — missing `.env.e2e` falls back to `.env`; the provider backstop never boots → fixed now |
| 15 | R1 exit: "staff registers with unconfirmed email **and resends**"; "returning imported parent told already registered" | plan §12 | **MISSING in e2e** → being added |
| 16 | E2E complete for what is built | Roy | **PARTIAL** — 13 of 28 routes had no journey → being added |

## Found so far (to fix / log)
- E2E code not linted by `make check` (`lint` = resources/js only) → fix now.
- README has no e2e section → fix now.
- AddStaff reveals the account name for an existing email ("Pia Parent is now …") — admin-only, minor → SECURITY.md.

## Progress while paused (2026-10-15)
- Rows 13, 14, 15, 16 → **DONE**: mobile-chromium added; global setup asks Laravel which DB `--env=e2e` reaches and
  refuses unless `*_e2e` (proven by hiding `.env.e2e`: the run refuses to start); journeys added for resend,
  already-registered, claim + Choose, NothingYet, sign-out, used link, Org family page, correct email, child status,
  unconfirmed filter, Excel download, welcome. **29 runs green, twice.**
- E2E found a product gap: **parents could not sign out** (only the staff menu had it) → `SignOutButton` atom on
  the family page, Choose, NothingYet and OrgShell (one owner).
- Per-device limits now come from `config/nxtgen.php` (20 in production; `.env.e2e` raises it — three browsers on
  one machine are one IP). Per-email limits unchanged. `RateLimitTest`.
- Lint + README fixed. Still no e2e: parent child edit (route exists, **no UI** — R1 remaining list), link EXPIRY
  (needs a clock; the used-link path is covered).
- **Next:** resume Phase 5 (Codex rounds A and B) over 7fe14cd + this working tree.
