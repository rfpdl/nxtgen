# 02 — Confirm a Squad's head count

Status: ready-for-agent
Blocked by: 01

## What
- Under each Squad on Who's in: a number field (pre-filled with the number checked in) and **"Confirm head
  count"**. `PUT /{church}/classes/{schedule}/squads/{squad}/head-count` with `{count: int 0–200}` — today's Session
  of that class must exist, the Squad must have a Check-in in it, same access as the door.
- New table `squad_head_counts` (session_id, squad_id, head_count, counted_at, counted_by; unique session+squad).
  Confirming again replaces it (latest wins, no history). Two leaders confirming at once → one row, no 500
  (savepoint + locking read, like CreateCheckInJob).
- Under the button: "✓ Confirmed 13:20 by Joy (4)" (church time, the staff member's first name).
- **Mismatch warning** when the confirmed count ≠ checked in now: "Checked in 5, counted 4." and, when some children
  are marked present, "Not marked: Isabel, Mia" (the checked-in children not marked). Recomputed on every load, so a
  later Check-in or Undo shows it too.

## Done when (what you see)
A Lifeshaper types 2 for Level 2 Girls (3 checked in, Amara and Sofia ticked), confirms, and sees "✓ Confirmed …
(2)" and the warning "Checked in 3, counted 2. Not marked: Isabel". Confirming 3 clears the warning.

## Test seams
Feature: access grid row, allowlist, validation (0–200, integer), no Session / Squad not in it → 404, other church.
Jobs: upsert latest wins; two-connection test (two confirms at once → one row). E2E: the Done-when journey.
