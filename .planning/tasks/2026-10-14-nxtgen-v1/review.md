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
~~QR poster, staff password backup, remembered landing choice (B9)~~ done · ~~photo + consent~~ done (wording to confirm) · ~~Sentry~~ wired, needs DSN · ~~`TenancyArchTest` +
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

---

# Review 3 — Release 1 since the first review (7fe14cd..3772d51)

**Status:** fixed, no decisions pending · **Date:** 2026-10-16 · **Reviewed:** `7fe14cd..3772d51` on `release-1` (base `e045817`)
against `plan.md` §2, §3.1–3.3, §4.4–4.5, §5 photo, §6 R1, §7.6, §10, §12; `docs/DECISIONS.md`; Review 2 (paused)
above; Roy's decisions in chat. Supersedes Review 2's open rows.

Intent sources found: plan.md, spar.md, review.md (reviews 1–2), review-spar.md, docs/DECISIONS.md (started
2026-10-15), STATE.md, TODO.md, SECURITY.md, git log, chat (Roy: Org/no /admin; People in R1; import once; E2E
important + complete; photos by parent OR Lifeshaper after parent consent; one satellite "CCF Dxb"; Sentry off;
consent wording OK; HEIC → convert).

## Intent ledger

| # | Commitment | Source | Status (before fixes) |
|---|---|---|---|
| 1 | Org service, `/{church}/…`, no `/admin` | Roy · plan §2 | DONE — OrgRoutingTest |
| 2 | Reserved top-level paths never a church | plan §2 | DONE — OrgRoutingTest, ChurchSeederTest |
| 3 | People: list/add/remove, grant ceiling, last-admin guard | D-R2 · §3.2 | DONE — revert-checked (7 guards) |
| 4 | Class-admin grants | §3.2 | PARTIAL — refused until R2 (TODO #9) |
| 5 | Production church + first admin from settings; never demo logins in prod | D-R2 · DECISIONS | DONE — ChurchSeederTest, revert-checked |
| 6 | One production satellite "CCF Dxb" | Roy 10-16 | DONE (config default) |
| 7 | Import once; rerun skips | D-R1 · §7.6 | DONE (pre-existing tests) |
| 8 | Allergy explicit yes/no; "no" keeps no details; nickname required | B6 · DECISIONS | DONE — revert-checked |
| 9 | Google Form parity (DGroup area/phone, lifestage, ministry) | §6 · B6 | DONE |
| 10 | Edit family/guardian/child: own family (parent); church/home-satellite admin (staff); never status/group/email | §3.2 · B5 | DONE — revert-checked gates |
| 11 | Full health shown to family + staff who can see the family | §3.3 | DONE |
| 12 | Duplicates: review, dismiss/confirm, nothing merged, reviewers = editors of the new family | §6 · §4.2 | DONE — revert-checked |
| 13 | System notes for email correction (§4.4), status, duplicates; staff-only | §4.4 · §6 | DONE |
| 14 | List search + filters (status, group, satellite, unconfirmed, duplicates); export matches; never widens | §6 | DONE — revert-checked |
| 15 | TenancyArchTest: tenant queries name the church | §3.1 · §10 | PARTIAL — only `Model::query()` statements (blind to `DB::table`/`->from()`) |
| 16 | Every Feature declares its matrix row | §3.2 · §10 | PARTIAL — Org + Family services only; `ShowChildPhotoFeature` (Auth) makes an access decision undeclared |
| 17 | Matrix rows tested for 7 actor kinds (anon, parent, staff, both, revoked, other-branch, other-org) | §3.2 | PARTIAL — rows tested, not the full 7-actor grid |
| 18 | Response-level tests: forbidden fields + photo URLs absent per actor | §3.3 | UNTESTED for the lite door view (no photo/health assertion) |
| 19 | Remembered landing, switchable, no off-site redirect | §4.5 | DONE — revert-checked |
| 20 | Staff backup password; staff only, while serving | D7 · DECISIONS | DONE — revert-checked |
| 21 | Printable QR poster | §6 | DONE (+ E2E) |
| 22 | Sentry with PII scrubber: "no child data in events" | §1 · DECISIONS | **DOCUMENTED-ONLY** — exception messages (SQL bindings: names, emails) not scrubbed |
| 23 | Photos: parent OR Lifeshaper takes/uploads, only after PARENT consent; staff never consent | Roy 10-16 | PARTIAL — a parent cannot consent without uploading (round A) |
| 24 | Photo consent withdrawal removes the photo | DECISIONS | PARTIAL — an in-flight upload can survive it; reads do not re-check consent (round A) |
| 25 | Photos private: signed links + viewer check; S3 private bucket in production | §5 · DECISIONS | PARTIAL — S3 adapter not installed (round A) |
| 26 | iPhone HEIC converted on the phone | Roy 10-16 | DONE (unit) — real-iPhone check TODO #16 |
| 27 | E2E complete for what is built; 3 browsers; never touches working data | Roy · §10 | DONE — 41 runs; DB guard proven; link expiry not E2E (TODO #13) |
| 28 | R1 exit criteria (§12) | §12 | PARTIAL — matrix grid (#17), import reconciliation on staging, `make e2e` green ✓ |

## Reuse and debt ledger

| New or changed concept | Closest existing implementation | Old path removed? | Judgment |
|---|---|---|---|
| `FamilyDetailsInput`, `GuardianDetailsInput` | rules inside `RegisterFamilyInput` / `GuardianInput` | yes — registration spreads them | good refactor |
| `GuardianFieldset`, `FamilyFieldset` | markup inside `RegisterFamilyForm` (267 → 141 lines) | yes | good refactor |
| `Family::scopeMatching` | matching inside `SearchFamiliesJob` | yes — both jobs use it | good refactor |
| `DuplicatePairData` | `FamilySearchResultData` (reused for both sides) | n/a | reuse |
| `GuardianEditData` | `GuardianDetailsData` (+ email) | n/a | necessary addition — email must not be editable here (§4.4) |
| `FamilyDetailsData` | family fields inside `FamilyRegistrationData` | no | **second copy** — justified for now (create vs edit DTO); third copy = consolidate |
| `GetChildWithFamilyJob` | `GetOrganizationChildJob`, `GetFamilyChildJob` | n/a | necessary (church read FROM the child); allow-listed in TenancyArchTest |
| `canManagePeople` / `canReviewDuplicates` | private `isAnyAdmin()` | n/a | fine — two rules that may diverge, one implementation |
| `SignOutButton` | three inline copies would have been needed | n/a | good refactor (one owner) |
| `AddChildPhotoFeature` / `StaffAddChildPhotoFeature` | shared `ChildPhotoInput` + `CreateChildPhotoJob` | n/a | necessary — different consent rules per actor |
| `UpdateDuplicateCandidateStatusJob` | `UpdateChildStatusJob` pattern | n/a | necessary |
| `CreateSystemNoteJob`, `GetFamilyNotesJob`, `Note` | none (table existed, unused) | n/a | necessary addition |
| `E2EServiceProvider`, `FamilyListQuery`, `config/nxtgen.php` | none | n/a | necessary |
| `AccessRowEnum` + `#[AccessRow]` | none | n/a | necessary (plan §10) |

**Complexity delta (7fe14cd..3772d51):** ≈ 60 public concepts added (Jobs, Features, DTOs, components, routes,
config keys); 0 removed; 3 consolidations (field rules, fieldsets, search matching). Parallel paths retained: 1
(`FamilyDetailsData` beside `FamilyRegistrationData`, trigger above).

## Ledger after fixes

| # | Was | Now |
|---|---|---|
| 22 Sentry "no child data" | DOCUMENTED-ONLY | DONE — `SentryScrubber::clean` strips SQL values, emails, phone digits from exception messages (unit test, revert-checked) |
| 23 Parent consent without a photo | PARTIAL | DONE — `GivePhotoConsentFeature` + "Yes, I agree" button; Pest + E2E: parent agrees → Lifeshaper takes the FIRST photo |
| 24 Withdrawal removes the photo | PARTIAL | DONE — child row locked + consent checked BEFORE the file is written; withdrawal locks and deletes EVERY file of the child; reads and links refuse without consent (read guard revert-checked). Two-connection test → TODO #20 |
| 25 Private S3 in production | PARTIAL | DONE in code — S3 adapter installed, real disk built in a test; staging upload/read/delete → TODO #21 |
| 15 TenancyArchTest | PARTIAL | PARTIAL — logged TODO #19 (before R2) |
| 16 Matrix row declared | PARTIAL | DONE — AccessRowTest covers the Auth photo feature (`SeeChildPhoto`) |
| 17 / 18 Matrix grid, response allowlist | PARTIAL / UNTESTED | logged TODO #18 — **Release 1 exit criterion, before shipping** |
| 28 R1 exit criteria | PARTIAL | PARTIAL — #18 and staging import reconciliation (#21) remain |

**Tally (28 rows, after fixes):** done 22 · partial 4 (#4 R2, #15, #17, #28) · untested 1 (#18) · missing 0 ·
documented-only 0 (was 1). **Debt ledger:** necessary 9 · good refactor 4 · reuse 1 · second copy justified 1 ·
missed 0 · duplicate 0 · premature 0.

## Rulings

| Round | Finding | Ruling |
|---|---|---|
| A | [high] S3 adapter not installed — production photos would fail | **Accepted** — installed; real S3 disk built in a test (failed before) |
| A | [high] upload racing withdrawal keeps a photo; reads ignore consent | **Accepted** — lock + consent check before writing; withdrawal sweeps every file; reads refuse; SECURITY #17 |
| A | [medium] parent cannot consent without uploading; test set consent in the DB (fixture blindness) | **Accepted** — consent-only endpoint + button; E2E covers the real path |
| B1 | S3 adapter (same as A) | **Accepted** |
| B2 | README production steps produce the demo church and log-only mail | **Accepted** — seeder refuses log/array mailer in production; README rewritten |
| B3 | staff added before the import get unlinked families and no email | **Accepted** — import reports them; resend → claim tested end to end |
| B4 | promised arch guards missing; reachability by basename | **Accepted, reclassified** — plan §10 now says built vs not; TODO #17, #19 (trigger: before R2) |
| B nb | 7-actor grid + response allowlist | **Accepted, reclassified** — TODO #18, R1 exit criterion |
| B nb | tenancy guard blind to builder queries; photo feature outside AccessRowTest | Builder: logged #19 · Photo: **fixed** |
| B nb | two-connection concurrency evidence | **Accepted, deferred** — TODO #20 (R2 harness) |
| B nb | People "parent who serves" fixture has no family | **Accepted** — real family; both pages asserted |
| B nb | ChurchSeeder reruns overwrite edits and reactivate a removed admin | **Accepted** — create-only; test |
| B nb | CI ignores pushes to release-1 | **Accepted** — `release-*` added |
| B nb | DECISIONS overstates "three browsers" | **Accepted** — states which journeys run where |
| B nb | E2E could reuse a non-e2e server | **Rejected** — `/__e2e/health` exists only under APP_ENV=e2e, whose provider refuses a non-`_e2e` DB; global setup checks again. Codex round 2 did not contest |
| B nb | FamilyDetailsData duplicates FamilyRegistrationData fields | **Accepted as a justified second copy** — consolidate on a third |
| Fix review 1 | [high] rolled-back save orphans the new photo outside withdrawal cleanup | **Accepted** — withdrawal deletes every file of the child; old file deleted after commit |
| Fix review 2 | [medium] withdrawal can sweep a pending upload | **Accepted** — lock + consent check before writing |
| Fix review 3 | [medium] commit failure after save leaves an orphan while consent stands | **Rejected as a defect** — withdrawal still removes it, so no photo outlives consent; storage tidiness → TODO #22 |

Not everything was accepted: one rejection on evidence (E2E server identity), one reclassified as housekeeping
(post-commit orphan). The high acceptance rate is mostly a signal about the work: the photo feature shipped with
a consent path missing and a race the tests could not see — both found here, not by the green suite.

## Outcome

**Fixed now** (each test failed before its fix): S3 adapter · Sentry message scrubbing · photo consent-only path ·
photo save/withdraw locking + every-file withdrawal + consent-checked reads · production mailer guard · ChurchSeeder
create-only reruns · import "awaiting claim" report + resend→claim test · AccessRow on the photo route · People
fixture with a real family · CI trigger · plan §10 / DECISIONS / README truthful.

**Needs a decision:** none — every finding fell inside Roy's decisions or the plan.

**Logged** (TODO, with triggers): #17 arch guards (before R2 freeze) · **#18 matrix grid + response allowlist
(before Release 1 ships)** · #19 tenancy/reachability guard gaps (before R2) · #20 two-connection concurrency tests
(R2 harness) · #21 staging S3 + import reconciliation (before R1 ships) · #22 photo orphan sweep (after R1).

**Codex sign-off:** round 2 (resumed thread) **agree**. Requested model `gpt-6-astra` on every call; the runner did
not report the model actually used.

**Gate:** `make check` green — 285 Pest (incl. 31 arch), 99 Vitest, PHPStan L10; `make e2e` 41 runs green.

---

# Review 4 — staff consent, Cloud storage, children list, group correction (4adeb84..b1453b1)

**Status:** fixed; 3 owner questions · **Date:** 2026-10-16 · **Reviewed:** `4adeb84..b1453b1` on `release-1`
against plan §3.2/§3.3/§6, DECISIONS (2026-10-16 entries), Review 3 outcome, TODO #17–#23, Roy in chat (consent by
parent or Lifeshaper; Laravel Cloud + private S3; first admin blivejesus@gmail.com; "complete the features first").

## Intent ledger

| # | Commitment | Source | Status (before fixes) |
|---|---|---|---|
| 1 | Consent recorded by parent OR Lifeshaper; no photo without it | Roy 10-16 · DECISIONS | DONE — Pest + E2E, scope revert-checked |
| 2 | Staff-recorded consent saved with who + noted on the child's history | DECISIONS | DONE — test asserts `photo_consent_by` and the note |
| 3 | Only the parent withdraws; withdrawal deletes | DECISIONS (Roy has not overruled) | DONE (Review 3) |
| 4 | Photos disk uses the bucket Laravel Cloud attaches | Roy · README · .env.example | **PARTIAL** — a blank `PHOTOS_BUCKET` (as .env.example ships) defeated the fallback (round A) |
| 5 | First admin blivejesus@gmail.com | Roy | DONE (config default + .env.example) |
| 6 | Children list, scoped to the viewer's satellites, filters never widen | plan §6 · §3.2 | DONE — scope revert-checked |
| 7 | Children list projection: allergy flag only, no health detail | §3.3 | DONE (ChildListRowData has no health fields) — UNTESTED as an absence assertion (→ TODO #18) |
| 8 | Group correction: church admin only | §3.2 | DONE — revert-checked |
| 9 | Group must be this church's and fit the child | build | DONE for church + gender (revert-checked); **PARTIAL** — retired groups accepted (round A) |
| 10 | Group change recorded (who/when) + history note | build | DONE |
| 11 | A refused group change shows on that child only | E2E finding | DONE — ChildAdminControls test |
| 12 | Every consent change on the history (parent too), atomic with the change | DECISIONS (read broadly) · round B | **PARTIAL → DONE** — one owner `UpdateChildPhotoConsentJob` (+ `Note::system`), transactions; tests incl. forced note failure |
| 13 | Children search finds what the box promises (parent name/email/phone) | UI copy · round B | **MISSING → DONE** — `Family::scopeMatchingContact`; siblings not dragged in (test) |

**Tally after fixes (13 rows):** done 12 · untested 1 (#7 absence assertion → TODO #18) · partial 0 · missing 0 ·
documented-only 0.

## Reuse and debt ledger

| New or changed concept | Closest existing | Old path removed? | Judgment |
|---|---|---|---|
| `GetChildrenJob` | `GetFamiliesJob` | n/a | necessary — different entity, same scoping rule (`visibleBranchIds`) |
| `ChildListQuery` / `ChildListFiltersData` | `FamilyListQuery` / `FamilyListFiltersData` | n/a | second copy, justified (no family toggles); consolidate on a third |
| `RegistrationListFilters` (was FamilyListFilters + `familyToggles`) | — | yes (renamed) | good refactor — one filter bar for both lists |
| `Family::scopeMatchingContact` | inside `scopeMatching` | n/a | good refactor — `scopeMatching` = contact OR children names |
| `Note::system()` | `CreateSystemNoteJob` body | yes — the Job calls it | good refactor — one way to write a system note |
| `UpdateChildPhotoConsentJob` (now writes the note) | `RecordPhotoConsentFeature` wrote it itself | yes | good refactor — one owner for consent + history |
| `GetGroupJob` | `GetGroupsJob` | n/a | necessary (one active group by id, church-scoped) |
| `UpdateChildGroupJob` | `UpdateChildStatusJob` pattern | n/a | necessary |
| `ChildAdminControls` | inline selects in `Org/Families/Show.vue` | yes | good refactor (page size + per-child error) |
| `RecordPhotoConsentFeature` / `GivePhotoConsentFeature` | shared Job | n/a | necessary — different actors and rules |

**Complexity delta:** ~14 public concepts added; 1 renamed (filters bar); 3 consolidations (contact matching,
system-note writing, consent+history). Parallel paths retained: 1 (list query/filters DTOs, trigger above).

## Rulings

| Round | Finding | Ruling |
|---|---|---|
| A | [high] blank `PHOTOS_BUCKET` defeats the Cloud bucket fallback | **Accepted** — `?:` fallback; test sets it blank (failed before) |
| A | [medium] retired groups can be assigned | **Accepted** — `GetGroupJob` active only; test |
| B1 | parent consent leaves no history | **Accepted** — one owner for consent + note, every path |
| B2 | blank bucket (same as A) | **Accepted** |
| B3 | children search promises parent name/email/phone, searches only child names | **Accepted** — parent contact matching; siblings excluded |
| B nb | consolidate the two list-query parsers / filter DTOs | **Rejected for now** — second copy for a different entity; a shared object would carry family-only toggles into the children path; consolidate on a third |
| B nb | staff consent E2E on phones | **Reclassified** — needs isolated per-run state; TODO #12 (breakpoints) |
| B nb | fixtures: real guardians, production age bands | Partly fixed (search test uses a real guardian + login); rest logged |
| B nb | owner questions (confirmation, volume, no-group) | **Needs Roy** — below |
| 2 | consent could commit without its note (no transaction) | **Accepted** — transactions in both consent-only Features; forced-failure test (failed before) |
| 3 | — | **agree**, nothing new |

Not everything accepted (one rejection, one reclassification). The acceptances were mostly real: a promised
history the parent path never wrote, and a search box promising more than it searched.

## Outcome

**Fixed now:** blank-bucket fallback · retired groups refused · consent history on every path, atomic · children
search by parent contact. Each test failed before its fix.

**Needs a decision (Roy):**
1. **One-tap staff consent.** A Lifeshaper's tap records "the parent said yes in person" with no confirm step. Options:
   (a) keep one tap — it is named, noted and the parent can take it back; (b) add "Are you sure the parent said
   yes?". Recommend (b): a mis-tap otherwise records consent the parent never gave.
2. **Children list size.** Fine at today's ~200 children (all on one page, filters help); at 500+ it gets long on a
   phone. Options: page it now (half a day) or when the list passes ~400. Recommend: later — log the trigger.
3. **A "no group" filter / clearing a group.** Children without a group cannot be filtered for, and a group cannot be
   cleared. Recommend: add the filter (small); clearing only if you want it.
(Still open from before: photo withdrawal **deletes** — say if you want hide-only.)

**Logged:** TODO #24 children list paging at ~400 children (if Roy agrees to defer).

**Codex:** rounds A + B, then 2 resumed rounds; final **agree**. Requested `gpt-6-astra`; actual model not reported.
**Gate:** `make check` green — 304 Pest (incl. 31 arch), 103 Vitest, PHPStan L10; `make e2e` 43 green.

**Roy's answers (2026-10-16), built:** 1 staff consent asks "Are you sure the parent said yes?" first · 2 families and
children lists paged, 50 per page (`ListPage`, `Pager`; Excel still has every filtered row) · 4 withdrawal HIDES the
photo (kept, shown to nobody, back on a new yes) — SECURITY #19, TODO #25 (delete action) · 3 "no group" filter:
explained why a child can be groupless (outside 4–12 on the Moving-Up date, or a mistyped birthday); awaiting Roy.
