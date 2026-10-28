# 03 — Astra review fixes (scan card)

Status: resolved
Raised: Astra (Codex adversarial review) over 365f099..02ccbe2, 2026-10-29.

## What
1. **[high] Two name edits at once stored a key for the wrong name** (Child's saving hook). Two requests load "Ana
   Reyes"; one changes the first name to Maria, the other the last name to Cruz. Eloquent writes only the columns that
   changed, so the row ended "Maria Cruz" with the key ANACRUZ — a name card for Ana Cruz would tick Maria. Fix: a
   save that changes any part of the name writes the WHOLE name and all its keys, from the same model, so the stored
   name and its keys always agree (the later edit wins the whole name).
2. **[medium] The key columns were too short** (varchar 191): three parts of up to 100 characters, and letters that
   grow when spelled out (ß → SS, æ → AE), could fail a save — and abort the backfill halfway through a migration.
   Fix: ASCII columns sized for the worst case (640 / 220, cut beyond that — never a failed save), compared exactly
   (ascii_bin); the backfill (`NameKey::fillIn`) safe to run again.

## Done when
Whatever order two name edits land in, the stored key spells the stored name; the longest names the forms allow save
and backfill.

## Comments

**2026-10-29 — built (agent), not committed.** Gates below.

1. `Child::booted()`: when a name part is dirty (or the child is new), the keys are worked out and the three name parts
   AND the three keys are all marked to be written (their loaded originals dropped), so the UPDATE carries the whole
   name with its keys. Test: two separately loaded models edit different parts → the stored keys spell the stored
   name, which is the later edit's whole name ("Ana Cruz"). Red against the old hook; a first attempt that rewrote
   only the name parts still failed it (the first-name key stayed MARIA), so the keys are written whole too.
2. Follow-up migration `2026_10_29_000300_widen_child_name_keys` (not an edit of 000200, so any database that already
   ran it is put right): `name_key` 640, `name_key_first` / `name_key_last` 220, ASCII / ascii_bin, then
   `NameKey::fillIn()`. The backfill moved out of 000200 into `NameKey::fillIn()` (batches of 500, same names → same
   keys, so re-running is safe); 000200 now only adds the columns, so it can no longer fail on a long name. `keysFor`
   cuts a key at the column's width. Tests: a child with 100 × ß / æ / ß saves with a 600-letter key; `fillIn` run twice
   fills long and ordinary names. The bypass guard exempts `NameKey::fillIn` (it writes only the keys, from the names
   it reads).
The tenancy guard lists `NameKey::fillIn` as cross-church on purpose (each child gets the keys of their own names).
Gates: `make check` green (Pest 1884 + arch 173, Vitest 416, PHPStan L10, migrate-check); `make e2e` green (94 passed,
1 skipped).

**2026-10-29 — Astra re-check, one more [high] (agent).** The backfill read a batch, then wrote keys by id alone: a
name edited between the read and the write had its fresh keys (written by the Child model) overwritten by keys of the
stale name. `NameKey::fillIn` now writes each row through `NameKey::fillInRow`, whose UPDATE names the child's id,
church and the EXACT names it read (`first_name = ?`, `middle_name <=> ?`, `last_name = ?`); 0 rows means the row
changed and already has its own keys — left alone. Test: read the row, rename the child through the model, run the
row's step → nothing written, the keys spell the new name (MARIAREYES). Revert-checked: the id-only write fails it.
Gates: `make check` green (Pest 1885 + arch 173, Vitest 416, PHPStan L10); `make e2e` green (94 passed, 1 skipped).

