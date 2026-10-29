# 01 — Report a lost or damaged card

Status: resolved

## What
On the staff family page, each child's card gets "New card…" → pick Lost or Damaged (+ optional note) → a Card
request (Asked, AED 10, not paid). The child's card then shows a Pill "Card: Asked · not paid" (or the current step)
instead of the button. One open request per Child; a second try shows the open one.

## Done when (what you see)
A Lifeshaper opens the Reyes family, taps "New card…" under Amara, picks Lost, and sees "Card: Asked · not paid".
A parent's My family shows nothing new.

## Test seams
Migration (card_requests: church, child, reason, note, step, fee_minor, paid_at/by, steps' at/by, open-child unique);
Enums (reason, step) unit tests; Create job + concurrency (two files at once → one request); Feature (who may, grid
row, allowlist); Vitest; e2e journey.

## Comments

**2026-10-29 — built (agent), not committed.** `make check` green (Pest 1940 + arch 173, Vitest 421 in 70 files,
PHPStan L10, build, migrate-check) and `make e2e` green (97 passed, 1 skipped as before). Looked at on a 390px phone
and at 1280 (button, picker, Pill); one fix: a line under the card block, so it does not run into the photo controls.

- **Table**: `card_requests` (migration 2026_10_30_000100) — church, child, reason, note (200), step, fee_minor,
  paid_at/by, approved/printed/given/refused at/by, refused_reason, created_by; `open_child_id` is a STORED column
  MySQL works out from the step (child while Asked/Approved/Printed, else null) with a unique key = one open request
  per Child, nothing to clear on close. Model CardRequest (+ factory; added to TenancyArchTest's tenant models).
- **Enums**: CardReasonEnum (lost, damaged, first_card; `feeMinor()` — `REPLACEMENT_FEE_MINOR` 1000 fils, first card
  0; `staffChoices()` = Lost, Damaged) and CardStepEnum (asked → approved → printed → given | refused; `isOpen()`).
- **Jobs** (app/Domains/Card): CreateCardRequestJob — inserts at Asked with the fee; on the unique key hands back the
  open one (locking read, as CreateCheckInJob), `wasRecentlyCreated` false. GetFamilyOpenCardRequestsJob.
- **Feature**: FileCardRequestFeature (Features/Card), POST `/{church}/children/{child}/card-requests`
  (`church.cards.store`, Card controller), input CardRequestInput (Lost/Damaged only, note ≤200). Who: new access row
  `card_request` = staff who can see the family (as photo). Stories #130 (files) and #131 (not for others, parents,
  first card, or a second open one — "Amara already has a new card on the way: Asked · not paid.").
- **Family page**: ShowFamilyFeature sends `card_requests` (CardRequestData `{id, child_id, step, step_label,
  fee_label}`) and `enums.card_reason`; stories #132 (staff see it) and #133 (a parent's page never carries it).
- **Screen**: ChildCardRequest (organism) in each child's card under Attendance: "New card…" → chips Lost/Damaged +
  note + "Ask for a new card" / Cancel; or the Pill "Card: Asked · not paid".
- **Tests**: enum units; job tests incl. two real processes filing at once → one request; GetFamilyOpenCardRequestsJob
  test; ChurchCardRequestsTest (who, refusals, one open, family page props); FamilyPageTest (#133); AccessMatrix row
  (15 actors; other-church guesses now 23); allowlist (`card_requests.*`, `enums.card_reason`); Vitest (5); e2e
  `tests/e2e/staff/cards.spec.ts` on all three browsers (added to the phones' testMatch).

**2026-10-29 — review fixes (agent), not committed.** `make check` green (Pest 1967 + arch 173, Vitest 423 in 70 files,
PHPStan L10, build, migrate-check); `make e2e` green (97 passed, 1 skipped).

- **One colour rule**: new CardFeeEnum (free / paid / not_paid, `of(fee, paid_at)`); the Pill is coloured by `fee`
  alone (amber not paid, else blue); CardStepEnum is plain words (`color()` muted). The model's `feeLabel()` is now
  `fee(): CardFeeEnum`.
- **Words built once**: CardRequestData `{id, child_id, step, fee, summary}` — `summary` "Asked · not paid" is the
  Pill's text and the Feature's "already on the way" message.
- `ProvidesEnumMetadata::toFrontend(?array $cases)` replaces `staffChoicesToFrontend()`; CardRequestInput uses
  `Rule::enum(CardReasonEnum::class)->only(staffChoices())`.
- **Open steps**: `CardStepEnum::openSteps()`; `tests/Feature/Schema/CardRequestInvariantsTest.php` pins
  `open_child_id` to `isOpen()` for every step (and close/reopen, the unique key, many closed ones).
- **Names**: route `church.card-requests.store`, `Features/CardRequest/`, `Controllers/CardRequest/`; migration
  renamed `2026_10_29_000400_create_card_requests_table`.
- **Note**: the Feature's trim removed — Laravel's TrimStrings + ConvertEmptyStringsToNull already do it (tested:
  200 letters kept, "   " → null, "  Torn  " → "Torn").
- `Family::childIds()` sub-query, used by GetFamilyOpenCardRequestsJob and GetFamilyCheckInsJob.
- **Tests**: race now runs each filing in a transaction that read first — swapping `lockForUpdate()` for a plain read
  fails it 3/3 (ModelNotFound); refiling after Given/Refused (Feature); order test proves "by id" (later-filed, earlier
  created_at); FamilyPageTest asserts no `card` key on the child; Vitest +2 (saved → closes; note error; Pill colour
  per fee); e2e reloads and sees the Pill again, staff context closed in `finally`.
