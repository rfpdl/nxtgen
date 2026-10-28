# 02 — Scan a name card at the door

Status: ready-for-agent

## What
- **Matching (server):** a scanned or typed code like `ADAMMATTHIASABERGOS` finds the child whose first + middle +
  last name, letters only (A–Z after removing accents), upper-cased, equals it — within this church. Store a
  generated/maintained `name_key` column on children (kept in step on every name change; backfilled in the
  migration; indexed with church_id). The door search: when the term is ONE word of letters that equals a child's
  name_key, show that child's family card (with the child highlighted/pre-ticked). Two children with the same key →
  show both families (rare; staff pick).
- **A USB/Bluetooth scanner** (types the code + Enter into the search box) works through that same path, no camera.
- **Camera scan (phone):** a "Scan card" button beside the search opens the back camera in a sheet, reads Code 39
  (use a maintained library that supports Code 39 on iOS Safari and Android Chrome, e.g. @zxing/browser — pinned;
  the native BarcodeDetector where present is fine as a fast path), closes on a read, and runs the search. Clear
  messages: camera refused / not supported → "Type the name instead"; no child for that card → "No child matches
  this card: ADAMMATTHIASABERGOS". Kit atoms only (discipline test).
- After a scan the found child's row is ticked, so "Check in 1 child" is one tap away (other children unticked).

## Done when (what you see)
On a phone, a Lifeshaper taps "Scan card", points at Amara's card, and the Reyes family appears with Amara ticked;
one more tap checks her in. Typing `AMARAJOYREYES` + Enter does the same (a desk scanner).

## Test seams
Unit: the name-key rule (accents, spaces, hyphens, apostrophes, ñ → N, middle name missing). Job/Feature: door
search by key (exact match only, this church only, two matches → both), name_key kept in step on create/edit/import.
Vitest: the scanner sheet with the decoder mocked (read → emits code; refused → message). E2E: the typed-code path
on all three projects (a camera can't run in CI); the camera button shows.
