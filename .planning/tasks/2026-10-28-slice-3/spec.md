# Slice 3 (Release 2) — child history + barcode at the door

Status: ready-for-agent
Decided: Roy 2026-10-28 (before bed): history on the staff child page, staff only; follow-up list skipped for now;
same-full-name children left as is; barcode = the existing NXTGEN name cards.

## The cards (Roy's photo + the "ForAttendanceRecordSheet" tab)
Code 39 barcode of the child's FULL name in capitals with no spaces: "Adam Matthias Abergos" → `ADAMMATTHIASABERGOS`.
Printed on the name cards children already have. So no new ID: the door matches the scanned letters against each
child's first + middle + last name, letters only, upper-cased, accents removed.

| # | Issue | Blocked by |
|---|---|---|
| 01 | Child history on the staff child page | — |
| 02 | Scan a name card at the door | — |

## Rules
tdd; two-axis review; `make check` + `make e2e` green; look step (screenshots) before commit; agents never
stash/reset/add/commit; commit + push to main per issue.
