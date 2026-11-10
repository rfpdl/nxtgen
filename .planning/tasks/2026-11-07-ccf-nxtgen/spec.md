# CCF NxtGen: one church, satellites in the address, platform admin, Sessions (TODO #32, #33, #35)

Status: ready-for-agent
Decided: Roy 2026-11-07 (grilling, three rounds); the bar and Batch C Roy 2026-11-08 (grilling, six rounds). The app serves CCF's NxtGen ministry only, at several satellites;
later apps (DGroup, family) may reuse its parts, so the church stays in the data.

## Batch A — the form and the header (TODO #32)
1. **Branding**: "CCF NxtGen" in the top-left bar of every page (sign-in, staff, parent), the browser tab title and
   the mail sender name.
2. **Lifestage**: a choice of Single, Married, KSP (`LifestageEnum`), optional. On the registration form it follows
   the first parent's relationship — Mother or Father → Married; anyone else → empty — until the person picks one
   themselves. Stored values that are not one of the three, and Registry values that are not, become empty
   (matched in any capitals). Old free text is not migrated: the app is not live.
3. **Allergies and illnesses**: stored as typed; shown as separate items wherever they are shown (a comma
   separates). Excel keeps the typed text in one cell.
4. **Birthday**: a calendar in the app's style (reka-ui DatePicker) with month and year selects; typing still works;
   no future date. Every birthday field uses it.

## Batch B — satellites in the address, platform admin (TODO #33)
5. **One church**: CCF, kept in the data, never in the address. Satellites `dubai`, `sharjah`, `abu-dhabi`, shown
   "CCF Dubai", "CCF Sharjah", "CCF Abu Dhabi".
6. **Addresses**: `/{satellite}/serve/…`, `/{satellite}/admin/…` (People included); parents `/family/…`.
7. **Registration**: `/{satellite slug or code}/register`. Each satellite has a code (nanoid) that never changes;
   the poster QR carries the code and the page forwards to the slug address. A platform admin may change the slug;
   printed QRs keep working. No "new code" button yet.
8. **Link QRs** carry the link itself (no code).
9. **Roles**: no church-wide Admin. Every Role is at a satellite or a class. A satellite has any number of admins;
   one person may hold Roles at several satellites. A satellite admin grants Roles at their satellite only; the last
   admin of a satellite cannot be removed.
10. **Access**: the base church feature checks the satellite in the address — Admin pages need the Admin Role there
    (or the class admin's pages); someone with only a Lifeshaper Role there is sent to that satellite's Serve; no
    Role there → 404.
11. **Landing**: an admin → Admin (can switch to Serve); others → Serve. The satellite: the last one used, else the
    only one, else a "Pick your satellite" list. A switcher in the menu.
12. **Platform admin** at `/platform`, set by an artisan command only: satellites (add, rename, change the slug),
    all people and every Role anywhere, church settings (time zone, card fee, moving-up date). A platform admin may
    open any satellite's Admin and Serve; history names them.

13. **The bar**: left, the logo and "CCF NxtGen"; right, the satellite (a dropdown when the person has more than one)
    and Sign out. The page's name shows only on a phone. The door's heading no longer names the satellite.

## Batch C — classes and Sessions: registration and check-in made really good (TODO #35)
Registration = a new Child joins; Check-in = a registered Child is recorded at a Session.

14. **A class is recurring or one-time.** Recurring: a weekday, a start and an end time, every week (no rule
    package: "does it happen today?" is the weekday). One-time: one date, a start and an end time (True Life,
    Christmas party). An event over several days is several one-time classes ("True Life Day 1", "Day 2"), each its
    own count. Every class has both times, the end after the start. The demo "Any day" class goes. One satellite each.
15. **A Session is one class on one date, and an admin opens and closes its check-in** (reverses DECISIONS
    2026-10-26 "never opened or closed"). Opened any time on its date, never on another day; closed by the admin or
    at midnight (satellite time); reopened the same day. A Child is checked in only to an open Session. Attendance
    lists every opened Session, 0 children included. Only an admin (the satellite's, or that class's) opens and closes
    until the Lifeshaper schedule (TODO #36) lets a scheduled Lifeshaper do it.
16. **The Session address**: opening gives the Session a code (nanoid). The door is `/<session code>/checkin`, Who's in
    `/<session code>/whos-in`; a Role at the Session's satellite is needed, else 404. A closed Session's Who's in
    stays readable.
17. **Serve** (`/{satellite}/serve`): "Open now" (Sessions with check-in open) and "Today" (today's classes not yet
    open, each with Open check-in for an admin); none → "No class today". The bar's dropdown switches between open
    Sessions.
18. ~~**Helper for the day**~~ — dropped (Roy 2026-11-11: "don't need helpers"): someone who helps is made a
    Lifeshaper on People. Built in issue 14, then taken out.
19. **Offline (later round, noted now)**: opening and closing need signal; an open door that loses signal keeps
    checking in and sends when back — those Check-ins are kept even if the Session closed meanwhile.

## Stories (numbering from 184)
Batch A: story#184 lifestage choice and its default (happy); story#185 an unknown lifestage is refused or left empty
(unhappy); allergies/illnesses and the birthday picker are screen behaviour, proven by Vitest and e2e.
Batch B: stories are written in its issue.
