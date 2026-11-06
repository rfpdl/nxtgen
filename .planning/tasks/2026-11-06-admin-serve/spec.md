# Admin and Serve — the staff pages split by who uses them (TODO #31)

Status: ready-for-agent
Decided: Roy 2026-11-06 (DECISIONS): staff addresses grouped by area — **Admin** and **Serve** — under the ministry:
`/ccf/nxtgen/admin/…` (manage families, children…) and `/ccf/nxtgen/serve/…` (the everyday app — check-in and
registration); church-wide admin (People) at `/ccf/admin/…`. Path, not subdomain (one sign-in across churches). Someone without
admin goes to Serve. A Lifeshaper can find, view, register and edit families in Serve — no exports, no merging
duplicates, no deleting. The **QR poster** shows the registration QR plus the church's own links (a Facebook group, a
WhatsApp group, or any link an admin names), each with its own QR; the admin sets the links.

## Words
- **Admin** — the church admin's pages. **Serve** — the pages for everyone who serves on the day (Lifeshapers, and
  admins at the door). Both are church-wide words, not NxtGen's: the app may grow into the whole-church app.
- **Link** (new, GLOSSARY): a church's own address shown on the QR poster — a label ("Parents' WhatsApp group") and an
  https address.

## Addresses (Roy: `/ccf/nxtgen/serve` — the ministry in the address now, so Elevate later is `/ccf/elevate/…`)

| Today | Area | New |
|---|---|---|
| `/ccf` | — | → the person's area: Admin (any admin Role) else Serve |
| `/ccf/people` (+ actions) | church Admin | `/ccf/admin/people` |
| `/ccf/families` (+ export), `/ccf/families/{id}`, `/ccf/families/new` | NxtGen Admin | `/ccf/nxtgen/admin/families…` |
| (the same, without export) | NxtGen Serve | `/ccf/nxtgen/serve/families…` |
| `/ccf/children`, `/classes`, `/attendance`, `/card-requests`, `/moving-up` (+ actions) | NxtGen Admin | `/ccf/nxtgen/admin/…` |
| `/ccf/door`, `/ccf/door/whos-in` (+ check-in actions) | NxtGen Serve | `/ccf/nxtgen/serve/door…` |
| `/ccf/poster` | NxtGen Serve | `/ccf/nxtgen/serve/poster` |
| `/ccf/register`, `/ccf/family` (public, parents) | — | unchanged (the printed QR keeps working) |

- **Admin area = admins only.** Every `/admin/` address is 404 for anyone with no admin Role (church or satellite) —
  on top of each page's own row. Serve is for any staff Role.
- **An action is under each area whose pages use it**, so a page never leaves its area. In both: the families list,
  register, the family, add a child, resend, edit (step 2 opens it to Lifeshapers), photo + consent, a Card request,
  give a card. Admin only: Excel, correct the email, status, Level, duplicates, Children, Cards' moves, Moving-Up,
  classes, attendance. Serve only: the door, Who's in, the poster. A Feature's redirect goes to the same area.
- **Route names follow the address:** `admin.people.*`, `nxtgen.admin.*`, `nxtgen.serve.*`. Pages shown in both
  areas (families list, family, register) get the area from a shared prop and build their links in it.
- **Menus:** Admin — Families, Children, Classes, Attendance, Cards, People, then "Serve". Serve — Check in,
  Families, QR poster, then "Admin" for admins. Password, Switch, Sign out in both.
- **Landing:** after sign-in, an admin goes to NxtGen Admin (families); anyone else with a Role to Serve (the door);
  parents to their family, as now.
- Code stays in the Church service (it owns every staff address under `/{church}`); its routes file has the three
  groups. No redirects from the old addresses: nothing is live yet (TODO #23).
- **Behaviour change in step 1 (Roy):** a Lifeshaper loses the Children list and the families Excel (Admin only) and
  keeps the families list in Serve.

## Lifeshaper in Serve (a permission change)
- Find a family (the door's search, church-wide lite view — as today), **the families list of their own satellites**
  (Roy: as today, without Excel), open a family (their satellites — as today), register a family and add a child.
- **New: edit** the family's details, guardians and children (names, phone, allergies, notes) — today church admin /
  home-satellite admin only (`EditFamily`). Still admin only: the email (Correct contact — it moves the account), a
  child's status and Level, merging duplicates, Excel.

## Links on the poster
- **Per satellite** (Roy): CCF DXB and House Church each have their own Links and their own poster; the registration
  QR is the same on each. An admin manages the Links of the satellites their Role covers (church admin: all).
- An admin adds, renames, reorders and removes a satellite's Links (label + https address; Facebook and WhatsApp offered
  as starting labels). Up to 6, so the poster still prints on one page.
- The poster: the registration QR first, then a QR for each Link with its label under it. QR codes made on the server
  (as today), sent as images.

## Step 3 design (Claude, from Roy's answers)
- **Table** `satellite_links`: church_id, satellite_id, label (≤ 60), url (https only, ≤ 500), position; up to 6 per
  satellite. Model `SatelliteLink` (tenancy list).
- **Admin page** `/ccf/nxtgen/admin/links?satellite=` — "Links" in the Admin menu: a satellite picker (the
  satellites the admin's Role covers; church admin: all), that satellite's Links in order; add (Facebook group and
  WhatsApp group offered as labels), rename / change the address, move up or down, remove. Access row `ManageLinks`:
  church admin at any satellite; satellite admin at their own (as Classes).
- **The poster** `/ccf/nxtgen/serve/poster?satellite=` — the satellites the person is assigned to (Roy: "assign you to
  satellite A, you can only do stuff in satellite A"; church-wide: all); a picker only when there is more than one.
  The registration QR, then each Link's QR with its label. QR codes made on the server, sent as images.
- **Safety:** a Link's address must start with `https://` (no `javascript:`, no plain http); shown as text and a QR,
  never as raw HTML.

## Order of work
1. **Move the addresses** — **done 2026-11-06**: the three groups in the Church service's routes, RequireAdminArea,
   RouteArea (+ `areaRoute` on the screens), a menu per area, the church address opening the person's area, every
   test's addresses; the access matrix has both areas' rows; `staff/areas.spec.ts`.
2. **Lifeshaper edits families** — **done 2026-11-06**: `AccessScope::canEditFamilyDetails` (= can open the family)
   for the family's details, guardians and children's details; everything else an editor does stays
   `canEditFamily` (admins). Claude, low-stakes: moving a family to another satellite stays an admin's (a
   Lifeshaper's form offers only the family's own satellite, and the server refuses a move); the email stays masked
   for a Lifeshaper.
3. **Links + poster**: migration, admin page, the poster's QRs (design above).

## Answered (Roy 2026-11-06, before the overnight run)
4. A Lifeshaper edits in Serve: **the family's details, guardians and children** (names, phone, allergies, notes) —
   for families of the satellites they serve (the ones they can open). Not the email, status or Level.
5. The poster: **a satellite you are assigned to is a satellite you work in** — "assign you to satellite A, you can
   only do stuff in satellite A". Someone in A and B picks between those two; a church-wide Role, every satellite.
   The same rule for managing a satellite's Links (admins of that satellite; church admin: all).

## Answered (Roy 2026-11-06)
1. Links: **per satellite**.
2. Satellite admins: **Admin, their own satellite** (as today).
3. A Lifeshaper in Serve: **search and the families list** of their satellites (no Excel).
