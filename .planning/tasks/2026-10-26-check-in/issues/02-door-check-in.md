# 02 — Check in at the door

Status: ready-for-agent
Blocked by: 01

## What
- `sessions` (church_id, satellite_id, schedule_id, date; unique schedule+date) created by the first Check-in.
- `check_ins` (session_id, child_id, squad_id, checked_in_at/by, undone_at/by; unique session+child; a re-check-in
  after Undo revives the row). Concurrency-safe (two phones at once → one Check-in).
- The child → Squad lookup (this Satellite, the child's Level and gender) deferred from refactor 05.
- **Door page** (phone first): today's date; class picker (the satellites the viewer's Role covers; a class with a
  weekday only on that day); count checked in; the existing church-wide search; family cards with children:
  tick + "Check in N children"; ✓ in + Undo; inactive → "Make active again?" then in; Graduated → refused with the
  reason; no Level → pick one (age suggestion pre-selected, saved, noted on history); ⚠ allergy flag; tap a name for
  health details. No photo, no phone number.
- Access: anyone with a Role at that satellite (church-wide roles: any satellite).

## Done when (what you see)
On an iPhone, a Lifeshaper picks **1PM**, searches "Cruz", ticks two children (one inactive), confirms, sees both
✓ and the count go up, undoes one; a child with no Level asks for a Level first; a Graduated child can't be ticked;
a visiting House Church child can be checked in at CCF Dxb.
