# Decisions

Product and technical decisions, newest first. Each one says what was decided, by whom, and why — so a later
change of mind is a new entry, not a silent edit. The plan (`.planning/tasks/2026-10-14-nxtgen-v1/plan.md`)
describes the design; this records the choices that shaped or changed it.

## 2026-11-11 (No helper for the day — Roy)

- **Helpers are taken out** (Roy, asked again in plain words: "a helper is a one-day extra Lifeshaper?" — "Don't need
  helpers"). Someone who helps at a class is made a Lifeshaper on People, and removed there when they stop. Spec item
  18 is dropped; issue 14 is wontfix. Gone: the Helper Role and the Session scope, the People choice, the helper's
  access while a Session is open, the Password rule for helpers (every staff member has Password again), and their
  tests and journey. The entries below about helpers are history.
- **Offline is its own step, with low-signal speed** (Roy: "offline capabilities + low network optimization in its
  own step") — not Batch C's issue 15. His early answers for it: offline, the phone keeps only the "has allergies"
  flag, not the full health notes; two phones checking in the same child → the first is kept, the other sees "already
  in"; offline, Undo, Who's in (this phone's) and registering a new family still work. Still to ask: which families a
  door phone saves.
- **Every page's props are generated from PHP** (TODO #43, Codex; Claude's call on how): a Feature renders one
  `…PageData` (`app/Services/X/DataObjects/` — a page's data is its Service's, as it brings several Domains' DTOs
  together, and a Domain may not import another), and the page declares `defineProps<XPage>()`, that same type. The
  generator now writes one flat module (`resources/js/types/generated.ts`) instead of a global `App.*` namespace,
  because Vue's compiler cannot follow the namespace into `defineProps`. A page's `enums` stay page-wide (read with
  `useEnum()`), hidden from its props type. The shared props are `SharedPropsData`, each lazy part a
  `Lazy::closure` so a partial reload still skips them. The door's health details stay an optional prop
  (`Lazy::inertia`). `PageContractTest` holds every Feature and page to this; its known-gaps lists started with all
  23 pages and ended the night empty. The forms Codex named post generated Inputs (`SatelliteInput`,
  `ChurchSettingsInput`, roll call's `MarkPresentInput`/`HeadCountInput`).
- **Closed classes stay under "Today" on Serve** with "Closed" and "See who was in" (Roy confirmed).
- **Safari Tab after the menu closes on a wide screen (TODO #40) fixed** (Roy: "fix it next"): focus goes to the menu
  row's current page instead of the hidden Menu button.

## 2026-11-10 (Codex review, whole codebase — Roy: "do a review with codex top to bottom")

Roy asked, going to bed, for a top-to-bottom Codex review: Lucid conventions, and unit/feature/e2e tests up to date.
Codex found the layering sound (no controller doing more than `serve()`, no Job calling a Job, no Domain importing
another) and these, fixed test-first:
- **A check-in decides on the child as they are now**: the child's row is locked and read again after the
  Session's, so a child Graduated (or given a Level) meanwhile is refused (or keeps that Level) — never made active
  again from the door's old copy.
- **A class opened today stays on Serve though its day was changed since**, open or closed; "Open again" only if it
  meets today. **A past one-time class cannot be changed on the server either** (only retired).
- **Opening a class's own address remembers its satellite** for the next sign-in, as a satellite address does.
- **Password is not in a helper's menu** (a backup password is for staff who stay).
- **Platform Settings could not be saved**: its Moving-Up date (ahead) used the birthday picker, which refuses any
  day after today — the picker now takes dates up to five years on when asked. Found by the new Settings journey.
- Tests added: helpers in the access matrix and response allowlists (an open helper exactly where a Home Lifeshaper
  is; one whose day is gone, nowhere); Session cases for roles, scopes and grants; reopening in the browser (same
  code, children kept, check-in works again); a Platform Settings journey. The duplicate-check lookup names its
  church.
- **Retiring a class while it is open closes its Session** (Astra, across Batch C): its own address then takes no
  check-in, Undo, present tap or head count — before, the code still took them while Serve had hidden the class.
- Left for later (TODO #42–#44): two console commands that skip the Lucid flow, hand-written page-prop types, a
  stronger close-vs-check-in race test; older enum words in Vue (TODO #41).

## 2026-11-10 (Issue 14 built — a helper for the day)

- **An admin adds a helper on People** (Roy): "Helper for the day · CCF Dubai · 1PM today" sits beside the other
  Roles; name and email, and the helper is emailed a sign-in link. The Role is held at one Session.
- **Only for a class open now** (Claude's call, reversible): a Session exists once an admin opens the class, so a
  helper is added at the door's time; one not opened yet, or closed, is not offered.
- **While that Session is open the helper may do anything a Lifeshaper may in Serve at its satellite** (Roy: "no need
  to do RBAC now, anything in serve") — every open door there, Families, registering — and never Admin. Once it
  closes, by an admin or at midnight, they find nothing ("not found"; signing in says "nothing here yet"); opened
  again the same day, they are back. People lists a helper on their Session's day only.
- Reviews (Astra, code + test): a helper's day is matched to THEIR church's date (another church's clock never
  counts); the platform gives no helper and no Role at a Session; a Helper Role is never a whole satellite's; a
  helper is not staff for a password; a retired class ends its helpers' day; platform People names helpers too.
- **The enums' words are the backend's only** (Roy 2026-11-10: "we have enum from backend, can we just share it to
  frontend instead of retyping"): a class's state on Serve ("Not open yet", "Open", "Closed") and its colour, and the
  door's "1PM is closed." come from SessionStateEnum in the page's data — no copy in Vue. A pill takes an enum's
  colour as it comes.

## 2026-11-10 (Issue 13 built — Serve: "Open now" and "Today")

- **Serve's first page (`/dubai/serve`) has two parts: "Open now" — the classes open, each "Go to the door" — and
  "Today" — the rest of today's classes** (spec item 17): not open yet ("Open" for its admin; a Lifeshaper sees "Not
  open yet", Roy's answer 5) and closed ones, which say "Closed" with "See who was in" (Claude's call, reversible: the
  spec names only open and not-yet-open). A part with no class is not shown; no class at all: "No class today".
- **On a class's door and Who's in, the bar names the class, and its dropdown lists the satellite's classes open now
  that the person may open — each a jump to its door** (Roy's answer 6). With nowhere else to go, the class's name
  alone; from a closed class, the one open now is enough (Astra). Who's in's 20-second refresh keeps the list
  current (Astra); a class retired while open is not listed (review). Every other page keeps the satellite dropdown.

## 2026-11-10 (Issue 12 built — the Session's own address)

- **Opening a class gives its Session a code — 8 letters and digits, the same kind as a satellite's registration
  code — and its door is `/<code>/checkin`, its Who's in `/<code>/whos-in`** (spec item 16). Everything done there
  (check in, Undo, present, head count, Close) is at the same code. Reopened the same day, it keeps its code (Roy).
  It is the one staff address without the satellite first (the address rule names it); the satellite is the
  Session's, so the bar and menu are that satellite's.
- **Who may open it: staff whose Role at the Session's satellite covers the class** — a Lifeshaper or admin there, or
  that class's class admin. Anyone else, a code nobody has, or another church's: "not found"; a stranger signs in
  first. A closed Session's door and Who's in stay readable (look, don't change); after midnight it shows closed.
- **The old door page goes** (Roy's answer 1), with no redirect: `/dubai/serve` is now Serve's first page — today's
  classes, each with "Go to the door" once open, "Not open yet" (Open for its admin) before, "See who was in" after.
  Opening lands on the class's door. The Serve menu's "Check in" is now "Today". Issue 13 splits the page into
  "Open now" and "Today" and adds the bar's dropdown of open classes (Claude's call, reversible: a first Serve page
  now, so no page points nowhere between the two issues).
- The code is matched exactly (upper case is another code).

## 2026-11-10 (Issue 11 built — an admin opens and closes a Session)

- **Opening a class makes its Session for the day; it is open until an admin closes it or until midnight at the
  satellite** (spec item 15) — a Session of another day is closed, with no job. Opening is only on the class's date
  (another day is "not found"); a closed Session opened again the same day is the same Session, so its Check-ins stay.
  This reverses DECISIONS 2026-10-26 "never opened or closed": the first Check-in no longer makes the Session.
- **Who opens and closes: an admin of the satellite, or that class's class admin** — never a Lifeshaper, until the
  Lifeshaper schedule (TODO #36).
- **While open, everything at the door works — check-in (late children too), Undo, Who's in taps and the head count;
  closed, it is looked at, not changed** (Roy). The door says "1PM is not open yet." / "1PM is closed." and hides the
  search; Who's in shows its Squads with no taps and no head count.
- **For now the admin opens and closes on the door page** (Claude's call, reversible): issue 12 moves the door to the
  Session's own address, issue 13 puts "Open" on Serve's first page. Closing asks once more, saying nothing can change
  after.
- **Every change at the door — check-in, Undo, a present tap, a head count — reads the Session again under its lock,
  and the church's date after the lock** (Astra): an admin closing it at that moment, or midnight passing while a
  request waits, comes before or after, never half-way. Who's in's 20-second refresh carries whether the class is
  still open. Closing a class another admin has just closed says "closed" (review), not an error page.
  Attendance lists every opened Session, 0 children included.
- In the browser tests 1PM and 7PM are open on the door's Saturday (as if an admin had opened them); the open/close
  journey uses its own one-time class, so the shared 1PM stays open.

## 2026-11-10 (Issue 10 built — a class is weekly or one-time)

- **Every class is weekly (a weekday) or one-time (one date), never both, and always has a start and an end time,
  the end after the start** (spec item 14) — held by the database. The "Any day (demo)" class is gone: to try the
  door on another day, add a one-time class for today. The Classes form asks "Every week" or "One date".
- **A one-time class whose date has gone is on Classes' "Past" list, newest first** (Roy's answer 4), with Retire
  but no Edit (Claude's call, reversible). Attendance keeps its Sessions.
- **A one-time class's date may be any date, past ones too** (Claude's call, reversible): a past date just lands on
  "Past". Two one-time classes may not share a name while both are listed (the same rule as weekly ones) — call it
  "Christmas party 2026", or retire last year's.
- **The schedules table was changed in place** (the app is not live — Roy): a working database needs
  `php artisan migrate:fresh --seed`.
- In the browser tests a request may ask for the day after (`X-E2E-Clock-Ahead`): the door's day moves with it, and
  the e2e sign-in lasts two days so a staff member stays signed in there.

## 2026-11-10 (Batch C, before building — Roy)

- **An admin opens and closes the Session, not "check-in"** (Roy: "i dont think we will close check in, we can close
  the session. coz sometimes there is a kid who comes lets say 3/4 of the class already"). While a Session is open
  everything at its door works — check-in, undo, Who's in, present, head count — for the whole class, late children
  too. Closed (by an admin, or at midnight), it is read-only: Who's in can be looked at, nothing changes. It can be
  reopened the same day.
- **A helper for the day may do anything in Serve at that satellite** (Roy, confirming his answer 2): every open
  Session's door, Families, registering — never Admin — while their Session is open. Issue 14's page checks are
  changed to match (they said another Session and Families were 404).
- **An admin adds a helper on People** (Roy): a choice beside Admin and Lifeshaper, for one of today's classes.

## 2026-11-10 (Batch C planned — classes and Sessions, TODO #35)

Roy answered the six open questions (`tasks/2026-11-07-ccf-nxtgen/batch-c-plan.md`):
- **The old door page goes** (`/{satellite}/serve/door`): Serve's "Open now" list takes you to each class's door.
- **A helper for the day may do anything in Serve** at that satellite (Roy: "no need to do RBAC now, anything in
  serve") — like a Lifeshaper — until the Session closes.
- **A Session reopened the same day keeps its code**, so a link already shared still works.
- **A one-time class after its date moves to a "Past" list** on Classes; Attendance keeps it.
- **Lifeshapers see today's classes not open yet**, marked "Not open yet" (no Open button).
- **On a class's page the bar's dropdown lists the satellite's open classes**; elsewhere, satellites as now.

## 2026-11-10 (Issue 09 built — the platform admin)

- **`/platform` is the platform admin's alone** (spec item 12): anyone else gets "not found", as if it did not exist.
  A person becomes one by `php artisan nxtgen:platform-admin {email}` (and stops with `--remove`) — never in the app.
  The demo admin is one. **The menu has a "Platform" link for the platform admin** (Roy 2026-11-10: "create shortcut
  for platform if im platform admin"), beside Switch; not on the platform's own pages.
- **Its pages**: Satellites (add one with its name and address — its QR code and Squads come with it; rename one or
  change its address — the QR code keeps working; open one), People (give or take away any Role at any satellite or
  class — a new satellite's first admin is given here; the last admin of a satellite still stays, as on a
  satellite's People), Settings (time zone, card fee, Moving-Up date).
- **The card fee is a church setting** (`churches.card_fee_minor`, 10 AED to start), no longer fixed in code. A card
  already asked for keeps the fee it was asked at; a lost or damaged card asked for after a change costs the new fee.
- **The platform's church is the first church** (Claude's call, reversible): the app is CCF's NxtGen only
  (DECISIONS 2026-11-07); with a second church the platform would pick one (TODO #39).
- **A new or moved address is refused if it is reserved** (`family`, `platform`, `pick`, …), **has a code's shape**,
  or is not words joined by single dashes (`al-ain`, never `al-ain-` or `al--ain`) — closes TODO #8.
- **In the browser tests the platform admin is a login of its own** (`platform@nxtgen.test`, no Role; review): the
  platform journey adds a satellite in every browser, and only the platform admin sees every satellite — so the demo
  admin there is an admin of its three satellites only, and the picker test checks exactly those. The demo data
  (`php artisan migrate --seed`) keeps the demo admin as the platform admin.
- Reviews (Astra, code and test): a satellite just added had no rename form until the page reloaded — fixed, and
  the journey now renames it at once. Two satellites added in the same instant with the same address: the second
  gets an error page instead of "taken" — left, with one platform admin.

## 2026-11-09 (Issue 08 built — landing and the bar)

- **After sign-in, staff go to the satellite they used last, else the only one they serve at, else "Pick your
  satellite"** (`/pick`, spec item 11); an admin lands in Admin, everyone else in Serve. The last satellite is saved
  on the account each time they open one of its pages (DECISIONS 2026-11-08), so it follows them to every device. The
  platform admin may open any satellite: last used, else the picker.
- **The bar** (Roy): the logo and "CCF NxtGen" on the left; on the right the satellite — a dropdown only for someone
  who may open two or more, landing on that satellite's home (Roy) — and Sign out. The page's name shows only on a
  phone; there the satellite has a line of its own under the logo (it did not fit beside "CCF NxtGen" on a 320px
  phone). The door's heading is today alone. "Satellite::openTo" is the one answer for which satellites a person may
  open (landing, picker, bar).
- **The welcome page's Sign in sits in the bar** (Roy 2026-11-08: "header with login"), in sight on a phone.

## 2026-11-09 (Issue 07 built — registration by address or code)

- **Each satellite has a registration code, 8 lowercase letters and digits with no look-alikes, made once and never
  changed** (Roy). Only the registration page takes it: `/{code}/register` forwards to `/{address}/register`; a code
  anywhere else is an address nobody has.
- **The poster's QR carries the code; the address printed under it is the satellite's address** (Claude's call,
  reversible): the QR outlives an address change, and the printed one is short enough to type. Link QRs carry the link.
- No "new code" button yet (spec item 7). **A satellite address never has a code's shape** (8 letters and digits of the
  code alphabet — Astra): addresses are looked up first, so one could otherwise take over another satellite's printed
  QR. The setup refuses one; the platform's add and change-address (issue 09) will too (`SatelliteCode::looksLikeOne`).

## 2026-11-09 (Issue 06 built — an Admin Role per satellite)

- **No church-wide Role** (spec item 9): a Role is at one satellite or one class. A satellite's admins give and remove
  any Role there (Admin too), and Admin at its classes; People shows that satellite's Roles only. A person may hold
  Roles at several satellites and appears on each one's People with that Role.
- **A satellite always keeps one admin**: removing its last admin is refused with the reason, counted under the
  satellite's lock (two admins removing each other at once leave one).
- **Moving-Up runs per satellite** (Roy, DECISIONS 2026-11-08): it moves that satellite's children, and "put back" is
  that satellite's last Moving-Up. A child whose family moved to another satellite is that satellite's: the old one's
  put back leaves them, and its last Moving-Up no longer names them (Astra). Moving-Up, put back, Level and status
  fixes queue on the church's row, and lock the family or child they check, so none overwrites another (review).
- **The first person the setup makes is the platform admin** (Roy): no satellite Role, so on no People list, but an
  admin at every satellite they open (spec item 12, brought forward from issue 09 so a new install has a way in).
  Until `/platform` (issue 09) they land on the first satellite. The demo admin is Admin at all three satellites (the
  bar's switch has places to go).

## 2026-11-08 (Batch B, before the overnight run — Roy)

- **The platform admin is not on any satellite's People list, but can open every satellite's Admin and Serve** (Roy:
  "doesn't appear in satellite admin list but we can have access just in case"). The first person the setup makes is
  the platform admin, with no satellite Role.
- **Switching satellite from the bar lands on that satellite's home** (Roy): Admin → its Families list, Serve → its door.
- **A satellite's registration code is 8 lowercase letters and digits** (Roy), e.g. `/k7m2q9xa/register`; no
  look-alike characters; never the same as a satellite address.

## 2026-11-08 (Issue 05 built — satellites in the address)

- **Every address names its satellite, never the church**: staff `/{satellite}/admin/…` and `/{satellite}/serve/…`
  (People too), parents `/family/…`, registration `/{satellite}/register`. Satellites `dubai`, `sharjah`, `abu-dhabi`
  ("CCF Dubai", "CCF Sharjah", "CCF Abu Dhabi"); a satellite's address is unique. `NXTGEN_SATELLITES` is
  `address=name|…`. Guarded by `tests/Architecture/AddressRuleTest.php`.
- **Controllers take models, never ids** (Roy: "if we do it right, we have less code to manage"): every id in an
  address is bound within the address satellite's church (`RouteBindingServiceProvider`); `RequireAssignment` checks the
  Role there and takes `{satellite}` off the route. Signed links are checked before any binding (Astra, SECURITY #23).
- **Someone who only serves at a satellite and opens one of its Admin pages is sent to its Serve** (spec item 10);
  any other act there is 404. Staff of another satellite get 404 — except the two acts for a visiting family, from
  their own satellite's address: resend its link, add a child to it.
- **A possible-duplicate pair is answered at the new family's satellite**: when the two families live at different
  satellites, the older one's page (at its address) no longer lists the pair.
- **The welcome page is empty for now** (Roy): the bar with Sign in. A family registers at its satellite's own
  address (its QR code or link); nothing on `/` picks a satellite.
- **Until the satellite picker (issue 08), a person lands at the first satellite their Roles cover, in the order
  satellites were added** (Claude's call, reversible): Dubai first.
- Until issue 06, a church-wide Role counts as the same Role at every satellite, and a church admin keeps Moving-Up,
  Level fixes and granting.

## 2026-11-08 (Batch B questions — Roy)

- **Satellite admins do everything a church admin did, for their own satellite** (Roy, Batch B start): Moving-Up
  and its put back run per satellite (that satellite's children only); fixing a child's Level by hand and full
  emails in the Excel download, for their satellite's families. There is no church-wide admin (spec item 9).
- **An Admin or Serve page shows only the satellite in its address** (Roy): Families, Children, Cards and print
  lists, Attendance, classes, Links, People. The satellite filter goes; to see another satellite, switch in the bar.
  The door still finds any child of the church (a visiting family is checked in).
- **The last satellite used is saved on the person's account** (Roy), so it follows them to every device.

## 2026-11-08 (Classes and Sessions — Roy)

- **A Session's check-in is opened and closed** (Roy, reversing 2026-10-26 "never opened or closed"): so the door
  lists only classes children can attend now, and Attendance holds no stray days. An admin opens it on its date and
  closes it, or it closes at midnight; reopened the same day. Only admins until the Lifeshaper schedule (TODO #36).
- **A class is recurring (weekday) or one-time (one date), always with start and end times** (Roy). A several-day
  event is several one-time classes, each its own count ("less complicated"). No recurrence-rule package: weekly is
  a weekday check; Sessions are made when opened, never ahead.
- **The door's address is the Session's code** — `/<session code>/checkin` (Roy: "based on uri, not query param"),
  so a door bookmark is one class on one day.
- **The bar** (Roy): logo and "CCF NxtGen" left; satellite (dropdown) and Sign out right; the page name only on a
  phone.
- Details: `tasks/2026-11-07-ccf-nxtgen/spec.md` items 13–19 (TODO #35); Lifeshaper management is TODO #36.

## 2026-11-07 (CCF NxtGen — Roy)

- **The app serves CCF's NxtGen ministry only, at several satellites** (Roy: "too much" to build for every church;
  later DGroup or family apps may reuse its parts). The church stays in the data, never in the address. Batch B
  (TODO #33, `tasks/2026-11-07-ccf-nxtgen/spec.md`) moves addresses to `nxtgen.app/{satellite}/serve|admin` —
  satellites `dubai`, `sharjah`, `abu-dhabi` — with an Admin Role per satellite (no church-wide admin), a QR code per
  satellite that never changes (`/{code}/register` forwards to the slug), and a platform admin area set by command.
- **"CCF NxtGen" is the brand** (Roy: "that is our branding"): top left on every page — a small line over the page's
  name, or the name itself on sign in and welcome; the tab title; the mail sender.
- **Lifestage is Single, Married or KSP, or none given** (Roy). The registration form starts on Married when the
  parent registering is a mother or father and leaves it empty for anyone else, until the parent picks one; their
  own pick stays. The Registry import reads the three in any capitals; anything else is none given — never guessed
  as Married. Old free text was not migrated: the app is not live yet (`make fresh`).
- **Allergies and illnesses show as items** (Roy): a comma separates them ("asthma, eczema" is two chips); stored as
  typed, so Excel and the forms keep the text whole.
- **Birthday: a calendar in the app's style** (Roy): day / month / year to type, or the calendar with year and month
  selects (three taps for a birthday years back); no day after today. reka-ui's DatePicker, already in the kit.

## 2026-11-07 (overnight — Claude, from Roy's list)

- **Class admin roles (TODO #9), built from plan §3.2's "Class admin (S)" column** (`tasks/2026-11-07-class-admin/`).
  Claude, where the plan is silent — the narrower answer, for Roy to confirm: a class admin works at their class's
  satellite as a Lifeshaper there (its families, register, edit details); at the door they see **only their class**;
  in Admin **only Classes** (edit or retire their class, add none) **and Attendance** (their class) — every other
  Admin page is 404; `/ccf` takes them to the door. Given on People by a church admin (any class) or a satellite admin
  (classes at their satellite), named "Admin · CCF DXB · 1PM". A retired class's Role gives nothing.

- **Delete a photo for good (TODO #25, SECURITY #19).** Claude, low-stakes, for Roy to confirm: the **parent** deletes
  their child's photo from their own page (a hidden one too), or an **admin who may edit the family** does it for
  them in Admin; **not a Lifeshaper** — it cannot be undone. The file goes, the child's history says who; consent is
  left as it was. Taking consent back still only hides (Roy 2026-10-16). The file is deleted only after the change
  is saved; one that will not delete is left for the nightly sweep (Astra).
- **Merge families / children (TODO #29): spec only**, as asked — `tasks/2026-11-07-merge/spec.md`: what moves,
  what clashes (two check-ins for one Session, two open card requests), locks, undo, and 8 questions for Roy.

## 2026-11-06 (iPad at the door — Roy)

- **Links on the QR poster (TODO #31 step 3, built).** Claude, low-stakes, for Roy to confirm: up to **6 Links a
  satellite** (the poster still prints on one page); **https only** (a QR parents scan must never open a plain-http
  or script address); the Links page shows every satellite the admin looks after, one section each, like Classes;
  "Links" is in the Admin menu; the poster's picker shows only for someone in more than one satellite. On paper the
  poster shrinks (registration QR ~5 cm, each Link's ~3 cm) so six Links print on one A4 or Letter page — a browser
  journey prints it to PDF and counts the pages (Astra). Adding, moving and removing Links lock the satellite, so two
  admins at once never pass the limit or put two Links in one place (Astra).
- **Lifeshapers edit families in Serve (TODO #31 step 2, built).** Claude, low-stakes, for Roy to confirm: moving a
  family to another satellite stays an admin's (a Lifeshaper could otherwise move a family out of their own reach,
  or into another satellite's); a Lifeshaper who edits still sees the email masked (correcting it is an admin's).
- **Overnight answers (Roy):** a Lifeshaper edits a family's details, guardians and children in Serve for the
  satellites they serve — not the email, status or Level. A satellite you are assigned to is the satellite you work
  in: the poster (and its Links) for someone in A and B is A's or B's, picked; a church-wide Role sees every
  satellite's. Then class admin roles (#9), "delete a photo for good" (#25), and the merge spec (#29) — spec only.
- **Addresses by area (Roy):** `/{church}/{ministry}/{area}/…` — `/ccf/nxtgen/admin/…`, `/ccf/nxtgen/serve/…`,
  church-wide admin at `/ccf/admin/…` (People). The ministry is in the address now, so Elevate later is
  `/ccf/elevate/…` and nothing moves again; families stay church-wide underneath. A path, not a subdomain: one person
  can be in several churches with one sign-in. Claude, low-stakes: a page shown in both NxtGen areas (the families
  list, a family, register) posts to its own area's address, so it never leaves it; a Lifeshaper loses the Children
  list and the families Excel (Admin only) and keeps the families list in Serve.
- **Staff pages split by service (Roy, TODO #31):** `/ccf/admin/…` for managing people, families and children;
  `/ccf/serve/…` for the everyday app (check-in, registration) — where anyone without admin lands. A Lifeshaper can
  find, view, register and edit families in Serve (no exports, merging or deleting). The QR poster shows the
  registration QR plus the church's own Links (Facebook group, WhatsApp group, or any link an admin names). Plan:
  `tasks/2026-11-06-admin-serve/spec.md`.

- **The password is never on /login (Roy):** /login offers the emailed link alone; the backup password is only on
  /loginp (the folded "Volunteer with a password?" link is gone).
- **Two short sign-in addresses (Roy):** **/loginp** is the volunteers' backup password, on its own page — the only
  place it is offered (only someone who serves; parents use the email link). **/loginc** clears this
  device's sign-in: signed out (the remember-me cookie too), the remembered landing page forgotten, then back to
  /login. Claude: /loginc is a plain GET so typing it is enough; the cost is that a link to it can sign someone out
  (SECURITY #22). The e2e password journey now uses a volunteer of each browser's own (E2ESeeder::VOLUNTEER_EMAIL): 15
  browsers sharing one account overwrote each other's password and hit the 5-tries-a-minute limit.

- **Moving-Up (TODO #27), Roy's answers:** only a **church admin** runs it, never automatic. Each child moves up one
  Level; from Level 4 the child becomes Graduated. Before it runs, the admin sees every child with their new Level and
  can **untick** anyone who stays. It remembers each child's old Level and status, so on the Moving-Up page the last
  Moving-Up lists every child moved, each with **"Put back"**, plus **"Put everyone back"** (Roy: "can we undo
  specific student as well?").
- **Moving-Up built** (`tasks/2026-11-06-moving-up/`). Claude, low-stakes, for Roy to confirm:
  - **Where:** a "Moving-Up" button on the Children page, church admins only — not a menu item. It is once a year,
    and a tenth menu item no longer fits the desktop menu row on one line.
  - **Who moves:** Active children with a Level. Inactive and Graduated children stay as they are; children with no
    Level are not moved, and the page says how many need one first.
  - **Exactly what the admin saw:** each ticked child is sent with the Level the page showed; if anyone changed
    since (another admin, a double tap), nobody moves and the page says to look again. Two presses at once run one
    after the other (the church's row is locked), so a child never moves up twice.
  - **Put back** only from the last Moving-Up, and not a child whose Level or status someone changed since (that
    change stands; the message names them). Each move and each put-back is noted on the child's history.
  - Each Level has "Untick all" / "Tick all" — e.g. to keep a whole Level back.
  - **Asks first**, and when the last Moving-Up was in the past 30 days it warns that another would move everyone up a
    second Level (code + test review: a second press would otherwise lock the first one in for good).
  - **One lock order for a child's Level and status** (Astra): Moving-Up, put-back, the Level correction and the status
    change all lock the church's row first, so they queue instead of overwriting each other or deadlocking. The door's
    check-in does not: it only sets a Level on a child with none and makes an inactive child active — children Moving-Up
    never moves — and locking it would make Saturday's check-ins wait on each other.
- **The test map (TODO #10)**, like admin-v2's: `make test-map` serves a page on 127.0.0.1 — every staff and parent
  route with its access rule and who gets in (✓ or 404), each story (happy and unhappy) with the tests that prove it,
  and Run buttons that run just those tests (Pest; Playwright on the browser picked, Chrome desktop by default) and
  show passed or failed. Claude, low-stakes: it reads the lists the tests use — the access matrix moved to
  `tests/Support/AccessMatrix.php`, the story scanning to `StoryClaims.php` — so it cannot drift from the suite. A Run
  names only tests the map lists, and a write needs the page's own token.
- **"No Level" filter** (plan §13 q5, Roy chose it for tonight): the Level filter on Children and Families has a last
  choice, "No Level" (`?level=0`) — the children with no Level yet (on Families: families with such a child). The Excel
  downloads follow the filters as always. Claude, low-stakes: on both lists, as they share one filter bar.
- Overnight order (Roy): Moving-Up, then the test map page (#10), then the "No Level" filter. Commit, merge, push when
  `make check` and `make e2e` are green.

- **E2E on an iPad in Safari, upright and on its side** (Roy: "yes we might have ipad at the door"). Two projects:
  `tablet-portrait-safari` 810×1080 (the menu still a sheet) and `tablet-landscape-safari` 1080×810 (the menu a row)
  — fifteen in all. Each has its own seeded families, like every browser.

## 2026-11-03 (overnight: browsers, race tests, link expiry, photo sweep — Roy)

- **E2E in Chrome, Safari and Firefox, upright and on their side** (Roy: "e2e supports safari, firefox, chrome. then
  work portrait and landscape as well"). Five projects added to the eight: `phone-landscape-safari` 844×390,
  `desktop-safari`, `phone-portrait-firefox` 390×844, `phone-landscape-firefox` 844×390, `desktop-firefox` — thirteen.
  Safari now has phone upright + sideways + desktop; Firefox the same. Firefox cannot pretend to be a mobile browser
  (no `isMobile`), so its phones are narrow touch windows (coarse pointer; the 44px rule still applies). Each new
  project has its own seeded families; the seed now numbers browsers with two digits (birthdays, phones).
- Claude, low-stakes (for Roy to confirm): **on a Mac, Firefox runs in Docker.** Playwright's Firefox (and the older
  build) will not start on this macOS ("Could not find profile folder"). `make e2e` starts `playwright-firefox`
  (docker-compose.yml, same version as `@playwright/test`) and the journeys drive it over `E2E_FIREFOX_WS`; CI is Linux
  and launches it itself. (iPad in Safari: added 2026-11-06.)
- Claude, low-stakes: **an e2e clock a journey can move** (TODO #13). Under APP_ENV=e2e only, a request carrying
  `X-E2E-Clock-Ahead: <minutes>` runs that much later (`MoveE2EClock`). Per request, so only that journey's browser
  sees it. The link-expiry journey opens a confirm link 7 days + 1 minute late and a sign-in link 2 hours + 1 minute
  late (both: "That link has expired or was already used"), then the same sign-in link at 1½ hours — it still works.
  So the e2e server never clears old sessions: Laravel does that on a random request, by that request's clock, and a
  week ahead it cleared every saved staff sign-in (the first full run lost 150 staff journeys to it).
- **Fix: two church admins removing each other at the same moment got an error page.** The new two-connection test
  (TODO #20) found it: locking only the admin rows deadlocked (MySQL 1213), and one admin saw a 500 (the rule held —
  one admin always stayed). `GetChurchAdminCountJob` now locks the church's row first, so the second waits and is told
  "This is the only church admin". Revert-checked.
- Claude, low-stakes: **the photo sweep** (TODO #22) — `php artisan nxtgen:photos-sweep` (`--dry-run` lists), daily at
  03:00 in the church's timezone (`nxtgen.church.timezone`). Church by church it deletes stored photo files no child points at, once they
  are a day old (an upload writes its file a moment before it saves the pointer). A hidden photo is pointed at, so it
  stays (SECURITY #19). **For Roy:** Laravel Cloud's scheduler must be switched on for it to run.

## 2026-10-30 (e2e by breakpoint — TODO #12; Roy)

- **Every journey runs at every layout band, not on chosen devices** (Roy: "breakpoints, not devices"). The app's
  breakpoints are Tailwind's `sm` (640px), `md` (768px) and `lg` (1024px, where the menu becomes a row), so eight
  projects cover them: base — `phone-small` 360×740, `phone-portrait` 390×844 and `phone-portrait-safari` (the same in
  Safari, for parents' iPhones); `sm` — `tablet-portrait` 720×1080; `md` — `tablet-edge` 768×1024 and
  `phone-landscape` 844×390; `lg` — `tablet-landscape` 1024×768 (the row menu's tightest) and `desktop` 1280×800.
  Phones and tablets are touch screens; the desktop is not. Admin journeys run on phones and tablets too.
- Claude, low-stakes (for Roy to confirm): **every screen a journey shows is layout-checked**
  (`tests/e2e/support/test.ts`) — after every page visit, at checkpoints (the menu, a form opened), and on every open
  page before its window closes; a journey that checked nothing fails. It fails when the page scrolls sideways, the
  menu does not fit (its row must be one line), or a control on a touch screen is under 44×44px (a label standing for
  a hidden file input counts); and a touch project whose browser does not report a touch pointer fails, so the 44px
  rule always applies. Journeys find menu items in the sheet or the row (`navTo`, `menuOf`) and decide "sheet or
  row" from the project's width. Each project has its own seeded families (door, roll call, scan card, the Ocampo
  boys, the Torres girls for the Level journey), so the runs never share a child and a retry needs no reseed.
- Claude, low-stakes (for Roy to confirm): **the menu is a sheet up to 1024px and a one-line row from there**, in
  base-size words (it was a row from 768px). The layout check found an admin's ten menu items wrapping onto two lines
  at every width from 768px up to 1280px; at 768–1023px they cannot fit one line at all.
- **For Roy:** on a phone held sideways (844×390) the bar takes a good part of the short screen before it scrolls
  away. It now shows the Menu button, not the row; whether the bar should be slimmer there is your call.

## 2026-10-29 (name cards — lost cards, new cards, printing; Roy)

- **Only staff file a Card request** (Lost or Damaged); a parent tells a Lifeshaper. Parents see nothing yet.
- **The AED 10 fee is marked paid / not paid** by staff (cash at the desk); the first card is free; no online payment.
- **Cards are printed from an Excel print list** shaped like the sheet's Barcode tab, so today's printing keeps working.
- **An admin approves before printing**: Asked → Approved → Printed → Given (or Refused).
- Claude, low-stakes (for Roy to confirm): every newly registered Child gets a free "First card" request
  automatically (imported children already have cards — none made); one open request per Child; Approve needs paid
  or free; marking Given is for any staff who may open the family (it happens at the desk).
  Spec: `.planning/tasks/2026-10-29-name-cards/`.
- Claude, low-stakes (issue 01, for Roy to confirm): **"New card…" sits in each child's card on the staff family
  page**, under Attendance: tap it, pick Lost or Damaged (chips), an optional note (200 letters max), "Ask for a new
  card". The card then shows a Pill "Card: Asked · not paid" (its colour from the fee alone — amber while not paid,
  blue when paid or free; the step is plain words; the server sends the words once, for the Pill and messages) in
  place of the button, until the request is Given or Refused. Filing again while one is open makes nothing and says
  "Amara already has a new card on the way: Asked · not paid." A child of any status may get one. **The AED 10 is a
  constant** (`CardReasonEnum::REPLACEMENT_FEE_MINOR`, in fils) until a church setting is needed. **One open request
  per Child is a database rule**: `open_child_id` is a column MySQL works out from the step (the child while Asked,
  Approved or Printed, else empty) with a unique key — closing a request frees the child with nothing for the app to
  clear, and two staff filing at the same moment make one (tested with two real processes, each in a transaction).
  The migration names the open steps itself (a run migration never changes); a schema test pins it to
  `CardStepEnum::isOpen()` for every step. Each step keeps its own
  when/who columns (approved, printed, given, refused + reason, paid) for issues 02–03. Who may file: the new
  `card_request` access row, the same people as a child's photo (staff who can see the family).
- Claude, low-stakes (issue 02, for Roy to confirm): **the first card is asked for by the app**, not a person
  (`created_by` empty), in the same transaction as the child — on registering a family (parent or staff) and on adding
  a child (parent or staff). The registry import, and so the demo and e2e seeders, make none
  (`RegistrationSourceEnum::getsFirstCard()`); no backfill. So a newly registered child shows "Card: Asked · free" on
  the staff family page at once, and "New card…" appears only once that first card is Given or Refused — a child who
  loses the first card before it is printed simply keeps the one request.
- Claude, low-stakes (issue 03, for Roy to confirm): **Cards is in the staff menu for every admin**: a church admin
  sees every satellite's requests, a satellite admin their own satellite's families' (Lifeshapers have no page). Tabs
  Asked · Approved · Printed · Given or refused; oldest first while there is something to do (first asked, first
  done), the closed tab newest filed first; 50 a page. Each row: the child's full name (as the card spells it), the
  family (its page a tap away) · satellite, reason — note, and the fee ("AED 10 · not paid" amber, "AED 10 · paid",
  "Free"). Moves: **Mark paid** while open and not paid; **Approve** only once paid or free (the button shows only
  then; an unpaid one is refused with "mark the AED 10 paid first"); **Refuse…** asks why (required), and only before
  printing; **Mark printed** one (the first "Mark all printed" is replaced by Print lists — see the issue 04 line);
  **Given** on the Printed tab, and beside the Pill on the child's card for any staff who may open the family. Each
  move is one conditional UPDATE (id, church, the step it starts from): a stale or second move changes nothing and
  says "Amara's card has already moved on: Approved · paid." Moving a request needs an admin over the family's home
  satellite (as editing it).
- Claude, low-stakes (issue 03 review, for Roy to confirm): **Cards has a "Find a child" search** — every word on
  the child's names (as the Families and Children lists read a search), on the tab chosen, kept when switching tabs or
  pages (`?tab=`, `?q=`). **Which move is allowed at which step lives in one place** (`CardMoveEnum`): the move Jobs'
  conditions and the buttons both come from it — the server sends each row's moves. A free first card answers "Mark
  paid" with "Amara's first card is free — nothing to pay." Money reads "AED 10", "AED 10.50" (fils never dropped).
  Cards' address is `/card-requests`; its moves are `…/mark-paid`, `approve`, `refuse`, `mark-printed` and `give`
  (`mark-all-printed` is replaced by Print lists, below).
- Claude, low-stakes (issue 04, and Astra's fix to 03; for Roy to confirm): **a Print list is kept on the server**
  (GLOSSARY "Print list"; code `PrintList`). "Make print list" on the Approved tab keeps exactly the approved cards the
  tab shows — the admin's satellites and their search, every page — by id, with who made it and when. The open lists
  sit on the Approved tab ("Print list · 12 cards · made Sat 31 Oct 10:31 by Grace — Download · Mark these 10
  printed") until none of their cards is left to print, so yesterday's list can still be marked. **Download** is its
  Excel, again whenever needed. **Mark these N printed** moves only its own cards still Approved and within the acting
  admin's satellites; the rest are skipped and the message says so ("10 marked printed; 2 had already moved on"). A card
  approved, renamed or moved into the admin's satellites after the list was made never joins it — so the earlier
  time-based cut-off (and its same-second gap, forged or shifted times) is gone (Astra found "Mark all printed" marked
  every approved card). A list shows each viewer only the cards they may see; one with none of theirs, or another
  church's, is not found. A card may be on two lists; marking either moves it once. The Excel is the sheet's Barcode
  tab: Full name, Nickname, Level, Squad, Barcode — the Barcode the child's stored name code, the one the door matches;
  the Squad by Level and gender; both empty for a child with no Level. It lists the list's cards still to print or
  printed (a printed one may need printing again), never one given or refused since. File
  `nxtgen-print-list-{id}-{date made}.xlsx`.

## 2026-10-28 (night — slice 3 decisions; Roy)

- **Child history: staff only, on the staff child page** (date, class, satellite, Squad, present). Parents don't see it yet.
- **Follow-up list (children who stopped coming): not now.**
- **Two children with the exact same full name in one Squad: leave as is** (no parent name added).
- **Barcode = the existing NXTGEN name cards**: Code 39 of the child's full name in capitals, no spaces
  (`ADAMMATTHIASABERGOS`). The door matches it against first + middle + last name, letters only — no new ID, the
  cards children already have keep working. A desk scanner typing into the search box works the same way.
- Claude, low-stakes (issue 01, for Roy to confirm): **the child's attendance sits inside each child's card** on the
  staff family page (Families → a family; the door's "Open family page" lands there), above the photo and edit
  controls: "Sat 31 Oct · 1PM · CCF DXB · Level 2 Girls" and "Present ✓" / "Not marked". Newest Session first (then
  the later class). The page carries every line and shows the last 12, then "Show all N" — a child's year is about 50
  lines. The year shows only when it is not this year (the church's year: "Sat 27 Dec 2025"). Never an undone
  Check-in; a parent's own page never carries it (tested). Past lines show the class's, satellite's and Squad's names
  as they are TODAY — renaming a class rewrites its history lines (no copy is kept per Check-in). Every line is sent
  and 12 shown; if a child's history grows large, "Show all" can fetch the rest when tapped instead.
- Claude, low-stakes (issue 02, for Roy to confirm; tightened after review): **scanning a name card**. The key is stored
  on each child (`name_key`, plus the first and last name as keys) and the Child model sets it on every save
  (`NameKey::keysFor`) — registration, edits by a parent or staff, the registry import, seeders — with a test that
  nothing writes children around the model; the migration fills in existing children. **If the NameKey rule ever
  changes, re-key every child with a migration.** Matching, safest first: **one child whose full name the card spells
  is ticked**; several such children → their families, nobody ticked ("tick the right one"); otherwise, for a code of
  six letters or more, a **close match** — the middle name missing on ONE side only (the card has none and the child
  has one, or the child has none and the card has one) — is shown, never ticked ("check this is the right child");
  never two different middle names, never fuzzier; a child whose first or last name has no letters matches nothing. A
  search is read as a card when it is one word of letters (two or more) or came from the camera (`?card=1`). A camera
  read no child matches says so in red and shows nothing else; a typed word that is no child's card is an ordinary
  search — just the families, no card note — and says "No child matches this card" (red) only when no family is found
  either (coordinator, after review).
  The card's child is pre-ticked only when active and not in yet. "Scan card" is a full-width button under the
  search on a phone; its sheet slides up from the bottom with a big Cancel. The decoder is `@zxing/browser` 0.2.1 +
  `@zxing/library` 0.23.0 (pinned), loaded only when a scan starts (its own chunk, ~120 kB gzipped); the native
  BarcodeDetector fast path was left out — one decoder, the same on every phone.
- Claude, low-stakes (slice 3 issue 03, Astra; for Roy to confirm): **a save that changes any part of a child's name
  writes the whole name and its keys** — two edits of different parts at the same moment end with the later edit's
  whole name (not a mix), and the card key always spells the stored name. The keys are ASCII columns wide enough for
  any name the forms allow (a longer spelling is cut, never a failed save), filled in by `NameKey::fillIn`.

## 2026-10-28 (roll call — slice 2 grilling; Roy)

- **"Who's in" is a second tab on the door page** (Check in · Who's in), same class and date; switching keeps the
  class. Same people as the door may use it (Lifeshapers and admins with a Role at that satellite).
- **Roll call is both**: tap each child present, or confirm a Squad's head count — either or both. When the count
  doesn't match the children checked in, a warning says so and names the ones not marked.
- **Latest wins**: marks and counts can be redone (start of class, after a break); only the latest is kept, with
  when and who. No history of earlier rounds. Undo at the door clears a child's mark.
- **Admins see it in Attendance**: present ✓ / not marked per child, the Squad's head count, also in the Excel.
- Claude, low-stakes: Who's in refreshes about every 20 seconds so new door check-ins appear; head count 0–200.
- Claude, low-stakes (issue 01, for Roy to confirm): **a tap on Who's in says nothing** — no green band; the tick
  is the answer, and a band appearing at the top would push the list down under the next tap (an error still shows).
  A child undone at the door before the list refreshes is refused with "Amara is not checked in any more.", not
  marked. `?squad=` naming a Squad with no one in now shows All (never a 404, so a refresh cannot break the page).
  The Squad chips are "All Squads" then each Squad's name, scrolling sideways like the class chips. Taps go to the
  server one after another and show at once. A row is the nickname and the allergy flag only — no "not marked"
  words; the empty box says it. Which class is picked has one rule for both tabs (GetChosenScheduleJob).
- Claude, low-stakes (issue 02, for Roy to confirm): **confirming a head count says nothing at the top** either — the
  Squad's own "✓ Confirmed 13:20 by Joy (4)" line is the answer. The field starts at the last confirmed count, else
  the number checked in, and follows the server until the leader types — and again once their confirm is answered
  (after review). Confirming is one atomic upsert. The warning and "Not marked: …" are worked out on the page from the Squad as shown (so a
  tap, a Check-in or an Undo shows at once); "Not marked" only once some child is marked. A count is refused with
  "Please type a number from 0 to 200." (on the phone too, before it is sent). On a phone the field and the button
  are stacked full width; from the sm breakpoint up, one row. Two leaders at once: one row, the later write kept.
- Claude, low-stakes (issue 03, for Roy to confirm): **Attendance shows the roll call** as a "Roll call" column
  ("Present ✓" / "Not marked") in the table and the last line of each phone row; under each Squad's heading, "Head
  count 4 · 13:20 by Joy" or "No head count", and the same warning as Who's in — one rule for both (`headCountWarning`,
  `resources/js/lib/rollCall.ts`). Only children still in are listed, so an undone child's old mark never shows. The
  head count field on Who's in follows the server again from the moment a count is SENT (a number typed while it is
  on its way is kept) — found by the e2e. The Excel's head count repeats on each row of its Squad.
- Claude, low-stakes (issue 04, raised by Roy; for Roy to confirm): **a search narrows with every word** — each word
  must hit the SAME family (a parent's name, email or phone digits, or a child's name), in any order: "Ana Reyes" is
  Ana's family, not every Reyes. Words under two letters are skipped, and only the first five are read (a long paste
  cannot make a slow query); a search with nothing left (one letter, only spaces) finds nothing — on the Children list
  too, which used to list everyone for a blank-ish search. A phone-shaped part ("050 123 4567", "+971 (50) …", four
  digits or more) is matched as one number against parents' phones, AND'ed with the name words around it ("Reyes 050
  444 5566"); digits inside a word such as an email are never compared with phones. The same rule runs the door, the Families list
  (and its Excel) and staff registration; on the Children list each word may hit the child or their parent, so
  "Janice" still finds Janice's children and "Amara Reyes" no siblings. **Who's in shows the first + last name** (no
  middle name, to keep the row short) small and muted under the nickname (inside the tick; no photo or phone). The door's own "Checked in" list still shows
  nicknames only.

## 2026-10-28 (later — ui-theme 03, parent and public pages on the kit; Claude, for Roy to confirm)

- **Parents get their own frame, FamilyShell** (AppShell, `narrow`: one column of max 2xl on a wide screen, the
  bar lined up with it): "My family" and the church in the amber bar; the menu is My family, Switch (only when
  they also serve) and Sign out — on a phone in the Menu sheet, like staff. The family page puts the children first
  (cards with Level pills, photo, Edit), then parents and guardians (a RowList) with "Edit family details".
- **Public and "nowhere else to go" pages get the bar with no menu** (AppShell without `items`): welcome, register,
  thanks, sign in, the emailed-link page, choose, nothing yet and the backup password. Each has one big primary
  button.
- **The welcome page links to registration**: "Register your family" (primary) and "Sign in". It has no church in
  its address, so the server sends `register_url` for the church this install serves (`nxtgen.church.slug`, the one
  ChurchSeeder creates; GetChurchBySlugJob, read lowercase) — the only new data in this issue. When that church is
  not in the database (or the setting is empty) it is null and only "Sign in" shows; the front door never fails.
- **The family page's heading is the family** ("The Reyes family", from the signed-in parent's last name; satellite
  and area under it): the bar already says "My family".
- **The kit gate has no known gaps left**; the "only shrinks" honesty check stays as `expectOnlyKnownGaps()` for the
  next guard (the JS twin of `tests/Architecture/KnownGaps.php`). FormField (now unused) is gone.

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
