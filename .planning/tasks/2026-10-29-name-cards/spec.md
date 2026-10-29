# Name cards — lost cards, new cards, printing

Status: ready-for-agent
Decided: Roy 2026-10-29: staff only file a request (parents tell a Lifeshaper); the AED 10 fee is marked paid / not
paid (cash at the desk, first card free, no online payment); cards are printed from an Excel print list shaped like
the sheet's Barcode tab; an admin approves before printing: Asked → Approved → Printed → Given.
Earlier (plan, 2026-10-14): first card free, replacement AED 10; same Code 39 full-name cards until the February
Moving-Up, then QR + reprint. Requirements: "Review lost card · Mark lost card as task for printing · Mark done".

## Words
**Card request**: one ask for a new Name card for one Child, moving Asked → Approved → Printed → Given (or Refused).
Reason: Lost, Damaged, or First card. _Avoid_: ID request, ticket, order.

## Who
- File a request (Lost / Damaged): staff who may open the family — Lifeshapers and admins.
- First card: made automatically when a Child is registered (parent or staff), free.
- Approve / Refuse / mark Paid / mark Printed: admins (church admin, or the admin of the family's home satellite).
- Mark Given: staff who may open the family (it happens at the desk).
- Parents: see nothing yet.

## Rules
- One open request per Child (database-enforced). Filing again shows the open one.
- Fee: First card = free; Lost / Damaged = AED 10 (a church setting). Approve needs paid or free.
- A lost name card cannot be switched off (the barcode is the name) — accepted.
- Refused needs a short reason; Given and Refused close the request.

| # | Issue | Blocked by |
|---|---|---|
| 01 | Report a lost or damaged card (staff child card) | — |
| 02 | A first-card request for every newly registered Child | 01 |
| 03 | Cards list for admins: approve, refuse, paid, printed, given | 01 |
| 04 | Excel print list of approved cards | 03 |
| 05 | ~~nickname + surname cards~~ — not needed: cards are full name (Roy's photo) | — |

## Rules of work
tdd; two-axis review + test review + Astra; `make check` + `make e2e` green; look step before commit; agents never
stash/reset/add/commit; commit + push to main per issue.
