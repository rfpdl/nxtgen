# Decisions

Product and technical decisions, newest first. Each one says what was decided, by whom, and why — so a later
change of mind is a new entry, not a silent edit. The plan (`.planning/tasks/2026-10-14-nxtgen-v1/plan.md`)
describes the design; this records the choices that shaped or changed it.

## 2026-10-26 (later still — check-in 03, Attendance built; Claude, for Roy to confirm)

- **The report is called "Attendance"** (menu item for admins only, `church_nav.can_see_attendance`); church admins
  see every satellite, satellite admins their own (`AccessScope::canSeeAttendance` / `attendanceSatelliteIds`).
- **It opens on** today's class with a Session (earliest class first), else the latest Session up to today at the first
  satellite, else that satellite's first class today. `?satellite=&class=&date=` (Y-m-d) pick; anything this admin
  does not see, or a date that is not one, is a 404 (GetAttendanceSelectionJob).
- **History stays reportable** (Claude — for Roy to confirm, after review): the class picker lists the active classes
  PLUS each retired class that has at least one Session, labelled "<name> (retired)"; a retired class that never met
  stays out (404). Retiring a class never hides its past Sessions.
- **Squads in Level order, then the mixed Squad, boys, girls; children by nickname.** The Squad heading says the
  Level (the one the child was checked in at), so there is no Level column. Times are the church's (Asia/Dubai). On a
  phone each child is one stacked row; from the sm breakpoint up, a table.
- **Excel columns**: Squad, Child nickname, Child full name, Checked in at, Checked in by — the page's own rows
  (never rebuilt), no health detail, phone or photo; every cell plain text (TextRow).
  `nxtgen-attendance-<satellite>-<class>-<date>.xlsx`. A child's full name has one source, `Child::fullName()`.

## 2026-10-28

- **Staff pages on the kit** (Claude, issue 02, after review, for Roy to confirm): a Level or Squad is always a Pill in
  its Level's colour. A Level travels in one shape, `LevelData {number, name}`, on every list, card, Squad and door
  row (replacing a bare number or a bare name); the Families list and search hits send each child as
  `{nickname, level}` — staff only, on the response allowlist. A list row that opens something is the whole row
  (bold name, ›, no underline). Emails break only after `@` or in the name. The allergy yes/no is two chips with
  neither picked (still never read as "no"), named by the question and carrying its error. Excel downloads are plain
  links (`AppButton external`).
- **The app's look is "Bold"** (Roy, from three directions: https://claude.ai/artifact/Jr2PHSMmx3VKXZcEmCVXw8): amber
  top bar with the logo, chunky pressable buttons (thick bottom edge), coloured Squad/Level pills in the age-level
  chart's colours (Level 1 blue, 2 yellow, 3 red, 4 green), Lexend (a typeface made for easy reading), big text and
  thumb-sized targets — modern, mobile-first and kind to older eyes. Built ONCE as a UI kit (tokens + atoms + app
  shell) that every page uses; a test blocks hand-styled buttons, inputs and colours outside the kit (TODO #30).
- **The kit as built** (Claude, issue 01): the primary button is the chart's green darkened to 7.6:1 with white (the
  chart green itself is 3.5:1); Level pills are a soft tint with dark text plus a dot of the chart colour (each pair
  ≥7.7:1). Text on a colour is always `on-<colour>` (`on-danger`, `on-level-N`); the focus ring is dark (`focus`)
  and, on a coloured band, the band's own `on-` colour. The door's checked-in list also sends each Squad's `level`.
- **A door child's row is two zones** (Roy, replacing the "Health" button beside the name and the "tick beside the
  name, pending Roy" note): on the left a tick zone, about 64px wide and the full height of the row (the checkbox,
  disabled for a Graduated child); the rest of the row is one button (a Disclosure: `aria-expanded`,
  `aria-controls`, a chevron) that opens the child's details in place — the health details (the same `health`
  partial reload and access rule as before, SECURITY #21; fetched on the first open only, then kept by child id),
  status and Level, and "Open family page" only when the viewer may open that family. The door's family now says so
  (`can_open`, AccessScope::canSeeFamily — the same rule as the registration search; on the response allowlist).
  Undo sits under the button, never inside it. "Make active again?", the Level picker and "Check in N children" are
  unchanged.

## 2026-10-27

- **No re-send button on the Children list** (Roy): it is the family's (parent's) email, so each child repeated it.
  It stays on the family page, unconfirmed rows of the Families list and the door search.
- **The door shows who is checked in** (Roy): it was empty until someone searched, so it looked broken. Under the
  search, and below any results found, "Checked in (N)" lists the chosen class's Check-ins today, newest first: the
  nickname (bold), this satellite's Squad, the time (the church's) and Undo (the same Undo as on a family card).
  With no one in: "No one checked in yet. Search a child's or parent's name to check them in." The page sends the
  list (`checked_in`, DoorCheckInData — nickname, Squad, time, the Check-in's id; no photo, phone or health detail)
  and "N checked in" is its length, so the two cannot disagree. One query for the list however many are in.

## 2026-10-26 (later — check-in 02, door built; Claude, for Roy to confirm)

- **Health details at the door** (Roy, confirmed): tapping any child the door finds shows their health details,
  visitors included, so a volunteer can see a visiting child's allergies (SECURITY #21).

- **Sessions live in `schedule_sessions`** (model `Session`): Laravel's database session driver already owns the
  `sessions` table. `check_ins.squad_id` is required — every child at the door has a Level by then, and a Squad the
  satellite lacks is made from the chart on the spot.
- **Undo only today's Check-ins**, at a satellite the Role covers. Older corrections wait for the admin report (03).
- **The door never changes a Level a child already has** — a picked Level only fills an empty one (correcting a
  Level stays church-admin only).
- **Health details on tap, for any child the door finds** (a visitor too) — see SECURITY #21; Roy may narrow it.
- **Browser tests run the door on a Saturday**: the e2e server (only) sets the door's day to the next Saturday, so
  1PM shows whatever day the suite runs. Every other clock is real.
- **Routes**: `GET /{church}/door`, `POST /{church}/classes/{class}/check-ins` (several children of one family, each
  with its own result), `PUT /{church}/check-ins/{id}/undo`. The Level picked at the door and "make active again"
  travel in the check-in body. "Check in" is the first item of the staff menu.

## 2026-10-26

Grilling session on check-in (Release 2), Roy. Terms in `GLOSSARY.md`; work in `.planning/tasks/2026-10-26-check-in/`.

- **Online first.** Offline (or low-signal) comes later — possibly one offline device for registration and check-in,
  which is far easier to sync.
- **Classes (Schedules) are made by admins** — church admins anywhere, satellite admins at their satellite: a name
  (e.g. "1PM", "Special class"), an optional weekday and start/end time. CCF Dxb: Saturday 1PM and 7PM.
- **A Session is never opened or closed.** Staff pick today's class and check children in; the first Check-in
  creates the Session (one per class per date). A class with a weekday shows only on that day; one without shows
  every day. **Replaces plan §3.2/§7's generated sessions and open/close**, and `lifeshapers_can_open`.
- **At the door** (Lifeshaper on their phone): name search church-wide (the existing server search — the plan's
  whole-list-on-the-phone waits for offline), tick children, check in; inactive → "Make active again?"; Graduated →
  refused; Undo; a child with no Level gets one picked at the door (age suggestion pre-selected, saved); an allergy
  flag; no photo or phone. A child may attend 1PM and 7PM the same day. A Lifeshaper sees the classes of the
  satellite(s) their Role covers; any child in the church can be checked in and joins THIS satellite's Squad for
  their Level and gender.
- **Roll call**: any staff who can check in may tap children present or confirm a Squad's head count. "Squad
  leader" is whoever looks after a Squad that day — not a Role. Class-admin roles (TODO #9) stay parked.
- **Reports (admins only)**: each Session by Squad with Excel; a child's history; a follow-up list of active
  children absent 3 weekends.
- **Not now**: check-out / pick-up; checking in Lifeshapers; Lifeshaper rotas.
- **Slices**: 1 = classes, door check-in, Session report (aim: 28 Oct). 2 = who's in by Squad + roll call. 3 = barcode
  scan (Code 39 by phone camera) + child history + follow-up list; QR after the February Moving-Up.

## 2026-10-17 (Roy, after first look in the browser)

- **No "Register a family" tab**: a "Register a family" button on the Families list (search-first step kept).
- **After staff register a family, they land on the new family's page** with the success message.
- **A family has one or more guardians** (mother, father, grandparent …) — added in both registration forms and on
  the family page. Still **one login per family**: the family email, the primary guardian's. Others don't sign in.
- **Level and Status sit inside the child's card** on the family page. A wider UI overhaul is wanted later
  (TODO).
- **No Duplicates tab**: possible duplicates are found with the Families list's "duplicates" filter. Merging two
  families or two children (e.g. a child registered again) needs its own spec later (TODO).

## 2026-10-17 (later still — re-send for a visiting family)

- **Anyone with a staff role in the church may re-send a family's confirmation email** (Roy, replaces the
  "anyone who can open the family" line below): a Lifeshaper at the door who finds another satellite's family can
  help that parent; sending an email changes no data. Offered on the family page, unconfirmed list rows and the door
  search results.

## 2026-10-17 (later — Roy's answers to the refactor's open decisions)

- **Squads now, the child→Squad lookup with check-in** — confirmed (Roy).
- **Casing** (Roy, replaces Claude's camelCase-page-props rule above): everything that crosses between the server and
  the browser is **snake_case** — page props included (`can_set_level`); the Vue prop-name-casing lint rule is
  switched off for this. Anything that stays inside PHP is **camelCase** — variables, arguments and the internal
  `#[NonClientFacing]` hand-off DTOs. Props passed between Vue components inside the browser stay camelCase
  (Vue's convention) — they never cross to the server.
- **Console service** for Features run from the terminal — confirmed (Roy): commands stay in `app/Console/Commands`
  and serve a Feature from `app/Services/Console`.
- **Re-sending the confirmation email** (Roy, replaces plan §3.2's role rule; closes TODO #28): anyone who can open
  the family may re-send it — church admin, the family's satellite admin, any Lifeshaper who can see it. Offered on
  the family page and on unconfirmed rows of the families and children lists. Parents already get a fresh link by
  registering again or asking for a sign-in link.

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
  _Clarified in refactor 08:_ a test claims a story with `@story#N` in its title, as aegis does; a comment
  does not count (StoryTokenTest).
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
