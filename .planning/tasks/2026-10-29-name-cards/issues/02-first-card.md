# 02 — A first card for every newly registered Child

Status: resolved

## What
Registering a family, or adding a child (parent or staff), makes a free "First card" request (Asked) for each new
Child. Imported children already have cards: none made for them, no backfill.

## Done when (what you see)
A parent registers Amara; the admin's Cards list shows "Amara Reyes · First card · free · Asked".

## Test seams
Operation tests (register, add child) make exactly one request per new child; import makes none.

## Comments

**2026-10-29 — built (agent), not committed.** `make check` green (Pest 1977 + arch 173, Vitest 423 in 70 files,
PHPStan L10, build, migrate-check); `make e2e` green (97 passed, 1 skipped). Looked at the new "Card: Asked · free"
Pill (blue) on a 390px phone and at 1280; nothing to fix.

- **Rule**: `RegistrationSourceEnum::getsFirstCard()` — parent (self) and staff yes, import no.
- **Operations**: RegisterFamilyOperation (each new child) and AddChildToFamilyOperation (the added child) run
  CreateCardRequestJob(FirstCard, `by: null` = the app) right after CreateChildJob, inside their existing transaction.
  Nothing when registration makes nothing (already registered, claim resent). No backfill; seeders use the import
  path, so seeded children have none.
- **Tests**: enum unit; RegisterFamilyOperationTest (one per child, both sources, free/Asked/app; import none; already
  registered none); new AddChildToFamilyOperationTest (added child only; import none); RegistryImportTest (none).
  Revert-checked: with `getsFirstCard()` always true, the three import tests fail.
- **e2e**: `tests/e2e/staff/cards.spec.ts` now opens with issue 02 — a parent registers Amara, the staff family page
  shows "Card: Asked · free" and no "New card…". Issue 01's journey moved to the seeded Cruz family's Ben (imported, so
  no request), since a freshly registered child now always has an open first card.
- **Not in e2e**: the Done-when's admin Cards list is issue 03.

**2026-10-29 — review fixes (agent), not committed.** `make check` green (Pest 1983 + arch 173, Vitest 423); `make e2e`
green (97 passed, 1 skipped).

- **Retry-safe e2e**: issue 01's half no longer uses the shared door family's Ben. E2ESeeder gives each browser its own
  imported Ocampo family of three boys (`CARD_CHILDREN` Jon, Kit, Ned; world.ts `CARDS`; E2EWorldTest checks them and
  that none has a request). The journey takes the first boy still offering "New card…", so a retry without a reseed
  uses the next one — proven with `--repeat-each=3` on all three browsers (12 passed). ("Lim" clashed with another
  journey's unique surname, hence Ocampo.)
- **One home for the rule**: CreateFirstCardRequestJob(child, source) — nothing for an import, else a free first card
  by the app; the unique key's error is not caught, so it fails with the child. Both Operations run it. Unit-tested.
- **Atomicity**: Operation tests make the card insert throw — registering creates no family, child, login or request;
  adding a child adds none. Revert-checked: filing the card after the add-child transaction fails the test.
