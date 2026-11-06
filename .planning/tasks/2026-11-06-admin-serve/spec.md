# Admin and Serve — the staff pages split by who uses them (TODO #31)

Status: ready-for-agent
Decided: Roy 2026-11-06 (DECISIONS): staff addresses grouped by service — **Admin** (`/ccf/admin/…`: manage people,
families, children) and **Serve** (`/ccf/serve/…`: the everyday app — check-in and registration). Someone without
admin goes to Serve. A Lifeshaper can find, view, register and edit families in Serve — no exports, no merging
duplicates, no deleting. The **QR poster** shows the registration QR plus the church's own links (a Facebook group, a
WhatsApp group, or any link an admin names), each with its own QR; the admin sets the links.

## Words
- **Admin** — the church admin's pages. **Serve** — the pages for everyone who serves on the day (Lifeshapers, and
  admins at the door). Both are church-wide words, not NxtGen's: the app may grow into the whole-church app.
- **Link** (new, GLOSSARY): a church's own address shown on the QR poster — a label ("Parents' WhatsApp group") and an
  https address.

## Addresses

| Today | Admin | Serve |
|---|---|---|
| `/ccf` (→ families) | `/ccf/admin` → families | `/ccf/serve` → door |
| `/ccf/door`, `/ccf/door/whos-in` | — | `/ccf/serve/door`, `/ccf/serve/door/whos-in` |
| `/ccf/families` (+ export, `/{family}`) | `/ccf/admin/families` (+ export, `/{family}`) | `/ccf/serve/families` (find, no export), `/ccf/serve/families/{family}` |
| `/ccf/families/new` | `/ccf/admin/families/new` | `/ccf/serve/families/new` |
| `/ccf/children` | `/ccf/admin/children` | — (the door search finds children) |
| `/ccf/people`, `/classes`, `/attendance`, `/card-requests`, `/moving-up` | `/ccf/admin/…` (same names) | — |
| `/ccf/poster` | `/ccf/admin/links` (set the links) | `/ccf/serve/poster` (print it) |
| `/ccf/register`, `/ccf/family` (public, parents) | unchanged | unchanged |

- **Landing:** after sign-in, anyone with an admin Role goes to Admin; everyone else with a Role goes to Serve;
  parents to their family, as now. An admin's menu has "Serve" (to work the door); Serve's menu has "Admin" for admins.
- **Who:** Admin pages are for admins (church or satellite, as each page's row says today); a Lifeshaper gets 404 there.
  Serve is for any staff Role. Actions (check in, give a card…) keep their rules; only their addresses move.
- **No redirects from the old addresses:** nothing is live yet (TODO #23), and the printed poster's QR opens
  `/ccf/register`, which does not move.

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

## Order of work
1. **Move the addresses** (no behaviour change): two services, Admin and Serve, from today's Church service; menus;
   landing; every test's addresses. Gate green.
2. **Lifeshaper edits families** in Serve: the access row, the matrix, tests first.
3. **Links + poster**: migration, admin page, the poster's QRs.

## Answered (Roy 2026-11-06)
1. Links: **per satellite**.
2. Satellite admins: **Admin, their own satellite** (as today).
3. A Lifeshaper in Serve: **search and the families list** of their satellites (no Excel).
