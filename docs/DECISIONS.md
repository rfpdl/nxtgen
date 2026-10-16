# Decisions

Product and technical decisions, newest first. Each one says what was decided, by whom, and why — so a later
change of mind is a new entry, not a silent edit. The plan (`.planning/tasks/2026-10-14-nxtgen-v1/plan.md`)
describes the design; this records the choices that shaped or changed it.

## 2026-10-17

Grilling session on refactoring Release 1 (Roy). Terms live in `GLOSSARY.md`; the work is
`.planning/tasks/2026-10-17-refactor/`.

- **Refactor before check-in, and before going live** (Roy). 28 Oct is a target, not a hard date; check-in waits.
  Laravel Cloud is set up after a local try-out.
- **Code uses the church's words: `Church` and `Satellite`** replace `Organization` and `Branch` (Roy, reversing his
  first instinct to keep "Organization" for non-church use). Both stay so another church can use the app.
  **`Guardian`** in code, "parent" on screen.
- **Groups are replaced by Levels and Squads** (Roy). A **Level** is a church-wide age band; the child's age only
  suggests it, the child *stores* their Level and keeps it until **Moving-Up** (never automatic). A **Squad** is a
  Satellite's split of a Level by gender (Level 1 mixed), taken from the age-level chart in config — no screen edits
  them. At check-in a child joins the Squad for their Level and gender at that Satellite, so visiting another
  Satellite needs nothing. **Supersedes 2026-10-16 "a church admin can correct a child's group"** — it becomes
  "correct a child's Level"; TODO #1 (mixed-group Moving-Up) disappears. Moving up out of Level 4 = **Graduated**.
- **Levels are one list for the whole Church**; per-Satellite Levels only if a Satellite ever needs them.
- Squads table built in refactor 05; the child→squad lookup waits for check-in (Claude — for Roy to confirm).
- **Schedule** = a Satellite's regular class time (shown as "class"; `Class` is a PHP reserved word); **Session** =
  one Schedule on one date — what children are checked into. A child has no "usual" Schedule.
- **Migrations are edited in place until the first deploy**, then only ever added.
- **Match aegis**, except: Features keep domain verbs (Add/Register/Withdraw — Jobs keep Create/Get/Update/Delete);
  no Storybook yet (TODO #26); `CLAUDE.md` stays short (long rule files crowd an agent's context).
- **Generated TS props are snake_case**, as in aegis. Jobs take Models, not ids. Enums and DTOs follow aegis's arch
  rules; Inputs also generate TS. Features carry `#[UserStory]`; tests say `story#N` (numbered from 1).
- **Casing of props** (Claude — for Roy to confirm): a page's TOP-LEVEL Inertia prop names are camelCase
  (`grantOptions`, `churchNav`) — they are Vue props, and `vue/prop-name-casing` and aegis agree; EVERYTHING
  inside them is snake_case (DTO fields, `auth.can_switch`, `enums.child_status`). Internal DTOs
  (`#[NonClientFacing]`, Input → DTO hand-offs) are snake_case too, so every mapper reads one way.
- **A Console service serves the registry import** (Claude — for Roy to confirm). `nxtgen:registry-apply` serves
  `ApplyRegistryImportFeature` from `app/Services/Console`. The service is named, like Public, Family and Church, for
  who it serves: the person at the terminal. Aegis serves a command's Feature from an existing service instead. Here
  that would be Church, where every Feature must declare an access-matrix row, and no staff row fits a terminal
  import with no signed-in user.
- **Workflow for the refactor: the mattpocock skill chain** (grill-with-docs → local issues → tdd → code-review)
  instead of review-spar — Roy is trying it out.

## 2026-10-16

- **Photos** (Roy):
  - A parent **or a Lifeshaper** can take (camera) or upload a child's photo — only once consent is recorded.
  - **Consent** is given by the parent on their family page, or **recorded by a Lifeshaper** the parent told in
    person — with an "Are you sure the parent said yes?" step. (This replaced an earlier "staff never agree".)
  - Every consent change is noted on the child's history (who, when), together with the change.
  - **Withdrawing consent HIDES the photo**: kept, shown to nobody, back if the parent agrees again. Roy chose hide
    over delete, against the review's recommendation — see SECURITY #19, TODO #25.
  - Photos are private: signed 30-minute links, checked against who is asking and against consent. Converted to an
    upright ~1600px JPEG on the phone before upload (iPhone HEIC included), re-encoded on the server (600px, no
    location or camera data). Consent wording approved by Roy.
- **Hosting: Laravel Cloud, MySQL, photos in a private S3 bucket** (Roy). **Sentry stays off** for now.
- **Production starts with one satellite, "CCF Dxb"**; more via `NXTGEN_SATELLITES`. **First admin:
  blivejesus@gmail.com** — everyone else is added on People.
- **Children list** beside the families list; **both lists paged, 50 per page**; Excel keeps every filtered row (Roy).
- **A church admin can correct a child's group** outside Moving-Up (plan §3.2); the group must fit the child's
  gender; noted on the history.
- **The children search also finds a child by their parent's name, email or phone** (review 4).

## 2026-10-15

- **Error reports never carry personal data**: Sentry's `send_default_pii` is hard-coded false (not an .env switch)
  and every event goes through `SentryScrubber`. Sentry stays off until a DSN is set.
- **Staff backup password works only while someone serves**; parents sign in by email link only (plan D7).
- **The landing choice is remembered per device** (cookie) and only ever sends someone to one of their own pages.

- **E2E on every built screen** (Roy: "it's important"), on its own database. Parent journeys and the
  Lifeshaper-at-the-door journey run on iPhone Safari, Android Chrome and desktop Chrome; admin journeys (People,
  duplicates, family admin, satellite scope, backup password) run on desktop Chrome only — admins work at a laptop. Sign-in is tested through the real emailed link. Tablet/breakpoint layouts are
  a TODO (#12).
- **Per-device rate limit is a setting** (`RATE_LIMIT_PER_DEVICE_PER_MINUTE`, 20 in production). Raised only for
  the browser tests, where three browsers on one machine look like one device. Per-email limits never change.
- **Allergies are an explicit yes/no** on every child form; a "no" stores no details. A blank box must never be
  read as "no allergies". Nickname is required (it goes big on the card).
- **People replies name the typed email, not the account's name** (SECURITY #16).
- **Import once; the app is the source of truth** (Roy, D-R1). The Google Sheet is loaded through the
  seeder/registry import; corrections afterwards happen in the app. No sync back to the sheet.
- **Manage users ("People") is in Release 1** (Roy, D-R2), including adding Lifeshapers. The production church and
  its first admin come from `ChurchSeeder` (NXTGEN_* settings); `db:seed` never makes demo logins in production.
- **"Admin" service renamed "Org"; no `/admin` in URLs** (Roy). Staff pages are for Lifeshapers too, so the URL
  names the church, not a role: `/ccf/families`, `/ccf/people`; satellite pages will nest (`/ccf/{satellite}/…`).
  Top-level paths (`/login`, `/up`, …) are reserved from church slugs.
- **The app may become a whole-church app** (events, DGroups, NxtGen, Elevate) — keep names ministry-neutral.
- **Security issues go on `.planning/SECURITY.md` and must not block progress** (Roy).
- **Login links expire in 2 hours** (Roy); confirm/claim links in 7 days.
- **Development is test-first** (Roy).

## 2026-10-14

- **Registration first** (stakeholders): Release 1 = registration (~20 Oct), Release 2 = check-in (28 Oct).
- **One login per person, no role picker**; a parent can also be a Lifeshaper with the same email. One login per
  family in v1; second-parent invite later.
- **Every child is registered** — no walk-in shortcut. Staff may register a family on their own device after
  searching first; the email may stay unconfirmed and never blocks check-in.
- **Families belong to the church, with a home satellite**; the door search is church-wide (same umbrella).
- **Groups = age + gender**, changed only at the yearly Moving-Up (admin-run).
- **Admin roles are scoped**: church, satellite, or class; several allowed.
- **Area is plain text in v1** (map picker later). **Offline check-in is a later round** (online first).
- **QR cards after the February Moving-Up**; existing Code 39 barcode cards keep working until then.
- **No stars/rewards** (Kuya Adam): children like their physical card stamped.
- **Stack**: Laravel 12 + Lucid + Inertia/Vue/TS + Spatie Data, the aegis/palo house stack — not the PoC.
