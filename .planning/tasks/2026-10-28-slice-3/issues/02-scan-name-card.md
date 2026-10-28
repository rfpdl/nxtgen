# 02 — Scan a name card at the door

Status: resolved

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

## Comments

**2026-10-29 — built (agent), not committed.** `make check` green (Pest 1861 + arch 173, Vitest 408, PHPStan L10, build,
migrate-check) and `make e2e` green (91 passed, 1 skipped as before). Looked at on a 390px phone and at 1280 (the door
with "Scan card", the sheet with no camera, a typed card with the child ticked, a card no child matches): fine as
built, after the focus fix below.

- **Key**: `App\Foundation\Support\NameKey` (first + middle + last, accents removed, A–Z, upper-cased; unit-tested:
  accents, spaces, hyphens, apostrophes, ñ → N, no middle). Columns `name_key`, `name_key_first`, `name_key_last` on
  children (`2026_10_29_000200_add_name_key_to_children`, backfilled, indexed (church_id, name_key)), set by the Child
  model on every save; `tests/Feature/ChildNameKeyTest.php` proves create (staff and import), edit, a plain save, and
  that nothing writes a child's names around the model.
- **Match**: GetChildrenByCardJob — this church, exact key; else first + last starting and ending the code; each child
  with its family as the door shows it. ShowDoorFeature reads a search as a card when it is one word of 6+ letters or
  `?card=1` (DoorQueryInput), sends `scan` (DoorScanData `{code, child_ids}`, allowlisted) and the families; a camera
  read with no match finds nothing and says so. Stories #128, #129.
- **Screen**: ScanCardSheet (organism: the "Scan card" button as a reka-ui Dialog trigger, a bottom sheet with the
  back camera, a big Cancel; refused / no camera → "Type the name instead"), `resources/js/lib/cardScanner.ts`
  (@zxing/browser 0.2.1 + @zxing/library 0.23.0 pinned, Code 39 only, loaded only when a scan starts — its own chunk),
  DoorFamilyCard `preTicked` (the card's child starts ticked unless already in or Graduated; no Level → the age
  suggestion), the door page's "No child matches this card: …".
- **Tests**: unit (NameKey), Job (exact, two matches, other church, the middle-name fallback and its limits), Feature
  (typed / lower case / camera, two families, no match, other church, the door's query count), allowlist (a scanned
  card's props), Vitest (cardScanner with the decoder mocked; the sheet: read → code + close, refused / unsupported
  messages, Cancel stops the camera, its button opens it; DoorFamilyCard pre-ticking), E2E on all three browsers:
  typing the browser's own card code (E2ESeeder's Reyes Amara with a middle name per browser, `SCAN` in world.ts,
  E2EWorldTest) shows her family with only her ticked and "Check in 1 child"; "Scan card" opens the sheet, says to type
  the name without a camera, and Cancel gives focus back to the button.
- **Fixed on the way**: on iPhone Safari focus did not come back to "Scan card" after Cancel (a tapped button is not
  focused there) — the button is now the Dialog's own trigger, so reka-ui returns focus to it.
- **Not tested**: the migration's backfill (migrations run on an empty test database); a real camera read (CI has no
  camera — the decoder is mocked in Vitest).

**2026-10-29 — review fixes (agent).** Safety first — never the wrong child ticked:
- **Close match tightened**: only a middle name missing on ONE side (the code is the child's first + last and the child
  has a middle name; or the child has none and the code starts with their first name and ends with their last) —
  never two different middle names (ANAJOYREYES ≠ Ana Maria Reyes), never fuzzier; tried only for 6+ letters.
- **Ticked only on ONE exact match** (CardMatchEnum exact / several / close / none, `ticks()`); several or close show
  the families with nobody ticked and a note ("tick the right one" / "check this is the right child").
- **No letters, no match**: a child whose first or last name has no letters ('123') matches no card; `?card=1&q=` and
  digits-only reads are ordinary searches. Revert-checked: the old loose fallback (2 tests fail), ticking every match
  (4 fail), dropping the letters guard (1 fails).
- **Typed card, no match**: the note shows and the ordinary search still runs (quiet "info" note when families are
  found; red only when nothing is). The exact key is tried for any one word of 2+ letters (BONG), the close match only
  for 6+.
- **Re-keyed family card**: searching "Reyes" then the card ticks her (the card is keyed by the scan code; the e2e now
  searches "Reyes" first).
- **ScanCardSheet**: each opening has its own number — a camera that starts after its sheet closed (or reopened) is
  stopped at once (revert-checked); unmount stops it; a decoder that cannot load says "This phone cannot scan here".
- **Pre-tick** only an active child not yet in (Graduated never; inactive shown for staff to decide) — Vitest.
- **E2E**: the camera refused (getUserMedia stubbed to NotAllowedError) shows the exact message; the camera path with
  no match says "No child matches this card: NOBODYHEREATALL" and shows no family. An unmocked Vitest checks the pinned
  decoder still has `decodeFromConstraints` and Code 39.
- **Standards**: the card decision, de-dup and DoorScanData now live in GetDoorCardJob (CheckIn domain; the Family
  job is gone), returning DoorCardData; `NameKey::keysFor` used by the Child model and the migration; clearer names;
  the bypass guard also catches upsert/insert/DB::update on children (with its own cases); the note is the new
  Notice atom (danger / warning / info); GLOSSARY has **Name card**; DECISIONS updated (rules above, and "re-key with a
  migration if NameKey changes").
Gates: `make check` green (Pest 1881 + arch 173, Vitest 416, PHPStan L10); `make e2e` green (94 passed, 1 skipped).

**2026-10-29 — item 4 corrected (coordinator).** A typed word that is no child's card is an ordinary search: just the
families, no card note. "No child matches this card: …" (red) shows for a camera read that matches nothing, and for a
typed card-shaped word only when no family is found either. Close / several notes unchanged. Tests, DECISIONS and the
REYES screenshot updated. Gates: `make check` green (Pest 1881 + arch 173, Vitest 416, PHPStan L10); `make e2e` green
(94 passed, 1 skipped).

