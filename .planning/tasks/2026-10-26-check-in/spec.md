# Check-in (Release 2) — spec

Status: ready-for-human (seams to agree with Roy)
Decided: 2026-10-26 grilling (Roy) — `docs/DECISIONS.md` 2026-10-26, terms in `GLOSSARY.md`.

## Why
Lifeshapers record who comes to each Saturday class on their phones, so admins know attendance per class and Squad.

## Slice 1 (aim: 28 Oct) — usable on a Saturday
| # | Issue | Blocked by |
|---|---|---|
| 01 | Classes: admins create and edit a satellite's classes | — |
| 02 | Check in at the door | 01 |
| 03 | Session report + Excel | 02 |

Slice 2 (who's in by Squad, roll call) and slice 3 (barcode, child history, follow-up) get their own issues after
Roy's look at slice 1.

## Rules for every issue
- "Done when" is what a person sees; after the gates, Claude opens the screen (screenshot) or Roy clicks through,
  before commit.
- Test seams agreed with Roy before building (below). tdd, two-axis code-review, `make check` + `make e2e` green.
- Agents never stash/reset or touch the index; the orchestrator stages and commits; merge to main and push.

## Proposed test seams (to agree)
1. **Feature (HTTP) per action** — create/edit class, check in, undo, door page, report, Excel: who may and may
   not (the access grid row), what the response carries (allowlist).
2. **Check-in Jobs** — one Check-in per child per Session even when two phones press at once (two-connection test);
   the child lands in this Satellite's Squad for their Level and gender; Undo then check in again works.
3. **E2E journeys** — admin makes "1PM"; a Lifeshaper on iPhone checks in a family (one inactive, one without a
   Level) and undoes one; admin opens the report and downloads Excel.
