# Moving-Up — the yearly step to the next Level

Status: ready-for-agent
Decided: Roy 2026-11-06 (DECISIONS): a church admin only, never automatic; the admin sees every child with their new
Level and unticks anyone who stays; the last Moving-Up can be put back — one child ("Put back") or everyone ("Put
everyone back"), on the Moving-Up page. Plan: Levels are stored and change at Moving-Up (yearly, admin-run), before the
February Moving-Up (TODO #27).

## Words
**Moving-Up** (GLOSSARY): the yearly step, done by people, where Children go to the next Level; from the top Level they
become Graduated. _Avoid_: promotion, graduation. "Put back": undo it for one child or everyone.

## Who
- Church admin only — the whole church at once. Satellite admins, Lifeshapers, parents: no page, no menu item (404).

## Rules
- **Who moves:** Active children with a Level. Level N → N + 1; at the top Level (4) the Level stays and the status
  becomes Graduated. Inactive, Graduated and no-Level children are not moved (Claude, low-stakes: an Inactive child
  is not coming; a child with no Level needs one first — the page says how many).
- **The admin unticks** anyone who stays. The page lists every child to move, by Level, ticked, with their new Level.
- **Exactly what the admin saw:** the form sends each ticked child with the Level the page showed. If any has changed
  since (another admin, a double tap, a Moving-Up just run), nothing moves and the page says to look again. The
  church's row is locked first, so two presses at once run one after the other — the second is refused.
- **Remembered:** each Moving-Up keeps its children with their Level and status before and after (who, when). Each
  child's history gets the usual note ("Level changed from Level 1 to Level 2." / "status changed from Active to
  Graduated.").
- **Put back** (only the church's last Moving-Up): a child goes back to the Level and status they had — only if they
  are still what Moving-Up made them; if someone changed them since, it says so and changes nothing. "Put everyone
  back" puts back every child not yet put back, and names any it skipped. A put-back child is marked put back (who,
  when) and shows "Put back".

| # | Issue | Blocked by |
|---|---|---|
| 01 | Moving-Up: the page (preview, untick), the move, its record — church admin only | — |
| 02 | Put back one child / everyone, from the last Moving-Up | 01 |

## Rules of work
tdd; code review + test review + Astra; `make check` + `make e2e` green; commit + push to main per issue (Roy:
"Commit, merge, push").
