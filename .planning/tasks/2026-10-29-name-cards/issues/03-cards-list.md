# 03 — Cards list for admins

Status: resolved

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

## Comments

**2026-10-29 — built (agent), not committed.** `make check` green (Pest 2152 + arch 173, Vitest 434 in 72 files,
PHPStan L10, build, migrate-check); `make e2e` green (100 passed, 1 skipped). Looked at 390 and 1280 (Cards tabs and
rows, Refuse…, Approved with "Mark all printed", Given on the child's card) — nothing to fix.

- **Scope**: AccessScope `canManageCards()` (any admin) and `cardSatelliteIds()` (null church admin; a satellite
  admin's own). Moves need `canEditFamily` (an admin over the family's home satellite); Given needs `canSeeFamily`.
  New access row `cards`; `card_request` now also covers giving. Menu: "Cards" for admins (`church_nav.can_manage_cards`).
- **Jobs** (app/Domains/Card): UpdateCardRequestAs{Paid,Approved,Refused,Printed,Given}Job — one conditional UPDATE
  each on (id, church, starting step [+ paid/free for Approve; not paid & not free for Paid]), returning whether it
  moved; UpdateApprovedCardRequestsAsPrintedJob (one UPDATE, the admin's satellites; returns how many);
  GetCardRequestJob; GetCardRequestsJob (tab, satellites, ListPage). CardListTabEnum (asked, approved, printed,
  closed). CardRequestRowData.
- **Routes/Features** (Features/CardRequest, BaseCardRequestFeature): GET `/cards` (`church.card-requests.index`), PUT
  `/card-requests/{id}/{paid|approve|refuse|printed|given}`, PUT `/card-requests/printed` (all). A move that finds the
  request elsewhere says "Amara's card has already moved on: Approved · paid."; Approve on unpaid says to mark it paid.
  Stories #134–#147.
- **Screens**: Church/Cards/Index (LinkTabs — a new atom DoorTabs now uses too — CardRequestRow organism, Pager,
  "Mark all printed" on Approved); ChildCardRequest gets "Given" beside the Pill when Printed.
- **Tests**: a unit test per Job (wrong step refused, conditional); two admins approving at once → one wins
  (revert-checked: without the step condition it fails); CardListTabEnum; CardRequestRowData; AccessScope;
  ChurchCardsTest (rules per story); AccessMatrix 7 new rows × 15 actors (other-church guesses now 28); allowlist
  (Cards page) + `church_nav.can_manage_cards`; Vitest (LinkTabs, CardRequestRow, ChildCardRequest Given, ChurchShell);
  e2e `tests/e2e/staff/cards-list.spec.ts` on all three browsers — asked at the desk → paid → approved → printed →
  given at the desk (Pill gone, "New card…" back), and a damaged one refused with a reason.
- **Retry-safe**: the Ocampo family is now six boys; both card journeys take the first still offering "New card…"
  (`tests/e2e/support/cards.ts`). Proven with `--repeat-each=2` on both journeys (15 passed). Cards lists oldest first,
  so the journey opens `?page=999` (clamped to the last page, where a just-filed request is).

**2026-10-30 — review fixes (agent), not committed.** `make check` green (Pest 2182 + arch 173, PHPStan L10, Vitest 440
in 73 files, lint, format, typecheck, build, migrate-check); `make e2e` green (100 passed, 1 skipped). A first run
of each timed out while the machine sat at load ~200 (the Docker VM; e2e 6.5 h, 16 unrelated journeys timing out);
both re-run clean once it eased, with no code changed between.

- **One rule for moves**: CardMoveEnum (mark_paid, approve, refuse, mark_printed, give) — `fromSteps()` feeds every
  move Job's condition, `allowedFor(step, fee)` the screen: CardRequestRowData and CardRequestData (desk: Give only)
  send `moves`; Vue renders exactly those (words from `enums.card_move`), with no step logic of its own.
- **Money**: `Money::aed()` ("AED 10", "AED 10.50"), used by the row's fee and Approve's "mark the AED 10 paid first".
- **Docblocks**: the conditional-UPDATE story and what really keeps another church out (the caller's
  GetCardRequestJob) said once, on CardMoveEnum; the Paid job's doc says what it checks.
- **Names**: `/card-requests` (`?tab=`, `?q=`); actions index, store, markPaid, approve, refuse, markPrinted,
  markAllPrinted, give; routes `church.card-requests.{…}` and paths to match.
- `CardRequest::scopeOfFamiliesAt()` used by GetCardRequestsJob and UpdateApprovedCardRequestsAsPrintedJob;
  BaseCardRequestFeature templated, subclasses `@extends …<RedirectResponse>`; LinkTabs works for any count (two a row
  on a phone, one equal row wider); ChildCardRequest.test back on `withEnums` (it now carries the church).
- **Search**: "Find a child" on Cards (SearchWords on the child's names, the tab chosen, kept across tabs and pages).
- **Tests**: CardMoveEnum, Money; free card → "Amara's first card is free — nothing to pay."; Refuse from Approved;
  search; exact ids per admin; Given race (desk vs Cards → one); Cards/Index Vitest ("Mark all printed" only on
  Approved with rows; tabs and pager keep tab + search; search; empty search). e2e searches by name: two boys paid and
  approved (each leaves Asked), one printed on its own and given at the desk, one by "Mark all printed" and given from
  the Printed tab (leaves it), one refused; the closed tab's newest row read. Ocampo family now nine boys;
  `--repeat-each=2` on both card journeys: 15 passed.

**2026-10-30 — Astra fix, first try (agent), replaced.** Astra [high]: "Mark all printed" ignored the search and
marked every approved card in scope. A first fix scoped it by the search and a time cut-off (`up_to`); it still had
gaps, so it was replaced by the server-held Print list (below, and issue 04).

**2026-10-30 — Astra fix, redone (agent), not committed.** The time cut-off (`up_to`) above still let cards that
were never on the downloaded list be marked (a reload moved the cut-off; same-second gap; a rename or a family moved
into scope; a forged or shifted time; a dropped search). Replaced by a **Print list held on the server** (issue 04):
"Make print list" keeps the tab's approved cards by id; "Mark these N printed" moves only that list's cards still
Approved and within the acting admin's satellites, and says how many had already moved on. No client time or search
reaches the move. `up_to`, `mark-all-printed` and UpdateApprovedCardRequestsAsPrintedJob are gone. Cards' query is a
Data input (CardListInput). Tests in issue 04's Comment.
