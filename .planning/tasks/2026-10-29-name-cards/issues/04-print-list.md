# 04 — Excel print list

Status: resolved

## What
On the Approved tab, "Make print list" keeps the approved cards the tab shows as a Print list (GLOSSARY), held on the
server. Each open list offers "Download" — an Excel in the shape of the sheet's Barcode tab (Roy 2026-10-29: columns
Full name, Nickname, Level, Squad, Barcode = the NameKey code, e.g. ADAMMATTHIASABERGOS) — and "Mark these N printed".
Downloading does not change a step; marking does.

## Test seams
Feature test on the Excel header + rows (admins only); the Barcode column equals what the door scan matches.

## Comments

**2026-10-30 — first design (agent), replaced.** A download and "Mark these N printed" scoped by the page's search and
a time cut-off (`up_to`); review and Astra showed it could still mark cards never on the downloaded list — replaced by
the server-held Print list below.

**2026-10-30 — redesigned (agent), not committed: a Print list is kept on the server.** `make check` green (Pest 2229
+ arch 173, Vitest 444 in 74 files, PHPStan L10, build, migrate-check); `make e2e` green (100 passed, 1 skipped).
Looked at Approved with an open list at 390 and 1280 — one wording fix ("Mark it printed" for one card).

- **Tables**: `print_lists` (church, made_by, timestamps) + `print_list_cards` (list, card request; primary key) —
  migration 2026_10_29_000500. Model `PrintList` (GLOSSARY "Print list"; tenancy guard), factory.
- **Jobs**: CreatePrintListJob (Approved, the admin's satellites, the search — every page — kept by id; none → no
  list), GetPrintListJob (this church only), GetPrintListCardsJob (its members the viewer may see, any step),
  GetOpenPrintListsJob (lists with a card the viewer may still print, newest first, counts as the viewer sees them),
  UpdatePrintListAsPrintedJob (members still Approved, church + satellite scope; one UPDATE on the membership table).
- **Routes/Features**: POST `/card-requests/print-lists` (MakePrintListFeature, MakePrintListInput), GET
  `/card-requests/print-lists/{id}` (ExportPrintListFeature — re-downloadable Excel), PUT `…/{id}/mark-printed`
  (MarkPrintListPrintedFeature: "10 marked printed; 2 had already moved on"); BasePrintListFeature: no Cards page,
  another church's list, or a list with none of the viewer's cards → 404. Stories #144, #145, #148–#151.
- **Screen**: Approved tab: "Make print list" (with the search), "Print lists to print" — PrintListCard: "Print list ·
  12 cards · made Sat 31 Oct 10:31 by Grace", Download, "Mark these 10 printed".
- **Tests**: jobs (exact ids; approved later / renamed / moved into scope after → never marked; other satellite's
  member left; given/refused skipped; a refuse racing the list's mark → printed or refused, never both; other church's
  list → not found); features (make with search, nothing → said; download rows + Barcode = name_key, moves none,
  re-downloadable; viewer sees only their cards, list with none of theirs or another church's → 404; mark counts and
  "moved on"; satellite admin marks only theirs); AccessMatrix 3 rows (other-church guesses now 30); allowlist
  (`print_lists.*`); WireCasingTest knows `{printList}`; Vitest (PrintListCard; page: Make print list only on Approved
  with cards, posts the search, shows open lists); e2e (search → make list → download, Barcode row → mark these
  printed → given from Printed tab); `--repeat-each=2` on both card journeys: 15 passed.

**2026-10-30 — review fixes (agent), not committed.** `make check` green (Pest 2240 + arch 173, Vitest 445 in 74
files, PHPStan L10, build, migrate-check); `make e2e` green (100 passed, 1 skipped). Looked at 390/1280: "Make print
list" now sits beside the "Print lists" heading, under the tabs.

- GetPrintListCardsJob and UpdatePrintListAsPrintedJob take the acting church (a second guard, not the list's).
- The Excel lists the list's cards still Approved or Printed — never Given or Refused (DECISIONS).
- One search cleanup, `SearchWords::tidy()` (spaces squeezed, blank → none, cut at 200), for Cards' list and Make
  print list; Cards' address read leniently by `CardListQuery::from()` (CardListInput dropped).
- GetOpenPrintListsJob uses `whereHas` (no HAVING on a count); CreatePrintListJob returns the list with `cards_count`
  (no second count query).
- Leftover text: issue 03/04 first designs folded to one line each; DECISIONS' "Mark all printed" lines marked
  replaced; AccessMatrix comments.
- Tests: open lists of another church never listed (church-admin case); a foreign card on a list is neither
  downloaded nor marked; once the House admin marks theirs, the list stays for DXB with its remaining card; a
  satellite admin's list with no search holds only their satellite's cards; download leaves out Given/Refused; a bad
  page and an over-long search are not errors; `SearchWords::tidy`; e2e flashes match the exact count shown
  (`--repeat-each=2`: 15 passed).
