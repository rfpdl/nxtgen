# 03 — Cards list for admins

Status: ready-for-agent

## What
A "Cards" page for admins: tabs by step (Asked, Approved, Printed, Given/Refused), each row child · family · reason ·
fee · paid. Actions: Mark paid, Approve (only when paid or free), Refuse (reason), Mark printed (one or all
approved), Mark given (also from the child's card at the desk, for any staff who may open the family). Conditional
updates so two admins can't move one request twice.

## Done when (what you see)
The admin marks Amara's Lost card paid, approves it, marks it printed; at the next class a Lifeshaper taps "Given"
on her card and the Pill goes away.

## Test seams
Jobs per step (conditional UPDATE, wrong step refused); Features (who may — satellite admin of another satellite
may not); access grid + allowlist; Vitest; e2e the whole road.
