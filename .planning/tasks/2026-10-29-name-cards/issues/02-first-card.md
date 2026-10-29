# 02 — A first card for every newly registered Child

Status: ready-for-agent

## What
Registering a family, or adding a child (parent or staff), makes a free "First card" request (Asked) for each new
Child. Imported children already have cards: none made for them, no backfill.

## Done when (what you see)
A parent registers Amara; the admin's Cards list shows "Amara Reyes · First card · free · Asked".

## Test seams
Operation tests (register, add child) make exactly one request per new child; import makes none.
