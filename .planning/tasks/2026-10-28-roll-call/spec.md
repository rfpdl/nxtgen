# Roll call (Release 2, slice 2) — spec

Status: ready-for-agent
Decided: 2026-10-28 grilling (Roy, Q1–Q5 "ok") — `docs/DECISIONS.md` 2026-10-28 (roll call), terms in `GLOSSARY.md`
(Roll call, Squad leader).

## Why
During a class, the Squad leader needs to know who is meant to be in their Squad and check nobody is missing —
by ticking each child, or by counting heads. Admins later see it in Attendance.

## Sketch (agreed)
```
┌────────────────────────────────┐
│ ▓▓ Check in              ☰   ▓ │  amber bar
│ [ Check in ]  [ Who's in ]     │  ← two tabs on the door page
│ CCF DXB · Sat 31 Oct · 1PM     │
│ Squad: (All)(L1)(L2 Boys)(L2…) │  ← chips: pick your Squad
├────────────────────────────────┤
│ ● Level 2 Girls      3 of 4 ✓  │  ← present / checked in
│ ┌────────────────────────────┐ │
│ │ ☑ Amara   ⚠ Allergies      │ │  ← tap = present
│ │ ☑ Sofia                    │ │
│ │ ☑ Mia                      │ │
│ │ ☐ Isabel   (not marked)    │ │
│ └────────────────────────────┘ │
│ [ Confirm head count: 4 ]      │  ← or type a number
│ ✓ Confirmed 13:20 by Joy (4)   │
└────────────────────────────────┘
```

| # | Issue | Blocked by |
|---|---|---|
| 01 | Who's in by Squad + tap present | — |
| 02 | Confirm a Squad's head count | 01 |
| 03 | Attendance shows the roll call (screen + Excel) | 02 |

## Rules for every issue
- "Done when" is what a person sees; after the gates, Claude looks at the screen (screenshots) before commit.
- tdd, two-axis code-review, `make check` + `make e2e` green.
- Agents never stash/reset or touch the index; the orchestrator stages and commits; merge to main and push.

## Test seams (agreed with Roy 2026-10-28)
1. **Feature (HTTP) per action** — Who's in page, mark/unmark present, confirm head count, Attendance + Excel: who
   may and may not (the access grid rows: same people as the door), what the response carries (allowlist), today only.
2. **Jobs** — present marks and head counts belong to one Session and Squad; latest wins; Undo clears the child's
   mark, a re-check-in starts unmarked; two Squad leaders confirming the same Squad at once → one head count, no 500
   (two-connection test, `tests/Support/Concurrency.php`).
3. **E2E journeys** — on a phone, a Lifeshaper checks in, switches to Who's in, picks a Squad, ticks children,
   confirms a count that doesn't match and sees who's missing; an admin opens Attendance and sees present ✓ and the
   head count.
