# Class admin roles (TODO #9)

Status: done
Decided: Roy 2026-11-06 (overnight list: "Class admin roles (#9)"); the plan §3.2's "Class admin (S)" column is the
rule. Where the plan is silent, Claude chose the narrower answer (marked *Claude*), for Roy to confirm.

## Words
**Class admin**: the Admin Role at one class (a Schedule — GLOSSARY "class") — e.g. "Admin of CCF DXB · 1PM". Stored
as an Admin Role assignment with scope "class" (`ScopeTypeEnum::Schedule`).

## What a class admin of class S (at satellite B) may do
| | Plan §3.2 | Here |
|---|---|---|
| Door search, register a family, add a child, resend a link | ✓ | ✓ (any staff, as today) |
| Check in / undo, Who's in, head count | events of S | **class S only** — the door offers S, not B's other classes |
| Open B's families, the families list (Serve), poster | not said | *Claude*: as a Lifeshaper of B — they work at B |
| Edit a family's details (Serve) | — | *Claude*: as a Lifeshaper of B (Roy 2026-11-06 opened this to Lifeshapers) |
| Classes | own series S | **edit and retire S**; not add classes at B |
| Attendance + Excel | S | **class S only** |
| Admin's families list, Excel, Children, Cards, Links, People, Moving-Up, duplicates, email, status, Level | — | **no** (404) |
| Grant roles | — | no |

- **Admin area**: a class admin opens only Admin's Classes and Attendance; every other Admin page is 404 for them.
  Their menu in Admin: Classes, Attendance, Serve. In Serve, "Admin" opens their Classes.
- **Landing**: `/ccf` takes a class admin to Serve's door (their class is there); an admin of a satellite or the
  church to Admin, as today.
- **Who gives the Role** (plan: "Lifeshaper/class-admin within B only"): a church admin, for any class of the
  church; a satellite admin, for a class at their satellite. On People, "Add someone" offers "Admin of <satellite> ·
  <class>" for each class the viewer may give. A retired class's Roles stay on People (they can be removed) but give
  nothing.

## Stories
- story#180 A church or satellite admin makes someone class admin of a class they look after (happy).
- story#181 Not at a class they do not look after; not by a Lifeshaper (unhappy).
- story#182 A class admin checks in at their class, sees its Who's in and its Attendance, edits and retires it (happy).
- story#183 Nothing of another class or another satellite; no other Admin page (unhappy).
