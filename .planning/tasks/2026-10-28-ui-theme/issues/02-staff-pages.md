# 02 — Every staff page on the kit

Status: resolved
Blocked by: 01

## Done when (what you see)
Families, Children, family page, register, People, Classes, Attendance, poster, duplicates cards: all Bold;
Squad/Level shown as coloured pills everywhere; the gate's staff known-gaps list is empty.

## Comments

**2026-10-28 — Claude (done, not committed).** Every staff page is on the kit: Families, Children, family page,
Register (search first + form), People, Classes, Attendance, QR poster, Backup password. PageHeader on each; Cards
for grouped content; EmptyState for empty lists; AppButton for every action (one primary per screen); TextField /
SelectField / TextArea / CheckRow for every field. Lists are stacked rows in a flush Card (Attendance keeps its
table from `sm` up, now `table-fixed` so the columns line up across Squads).

- **Level pills in colour** need the Level's number, so three response DTOs gained one field (allowlist test first):
  `ChildPortalData.level_number`, `ChildListRowData.level_number`, `AttendanceSquadData.level`. Nothing else in the
  data changed.
- **Kit additions:** `FileButton` (atom: the photo's take/choose picker, looks like a secondary button);
  `AppButton external` (a plain `<a>`, for the Excel downloads); ChoiceChips options take a `testid`; CheckRow's row
  tick lines up with the fields (no left padding). New organism `CorrectEmailForm` (kept the family page under 200
  lines). ResendLinkButton is now a secondary AppButton.
- **Control changes, same behaviour:** the allergy yes/no is ChoiceChips (same test ids, on the chips); the family
  ticks and privacy are CheckRows (Playwright `check()` unchanged). One e2e selector narrowed:
  `select[data-testid^="level-"]` (the Level pill's dot also starts with `level-`). Attendance's satellite and class
  lists send on the select's own `change`, so picking the same one again still reloads, as before.
- **Shared with parents:** the fieldsets, child card and forms are shared, so the parent register and family pages
  already look Bold too; their own pages (Login, ConfirmLink, PasswordLoginForm) stay for 03.
- KNOWN_GAPS: 27 → 3 (all parent/public, issue 03).
- Looked at on 390px and 1280px (every staff page, plus the family page with its edit forms open and the register
  form open); no sideways scroll. Fixed from the look: tick indent, Attendance column drift, class cards' buttons
  on the right on desktop.
- Gates: `make check` green; `make e2e` 75 passed, 1 skipped (the phone-only menu test on desktop, as before).

**2026-10-28 — Claude, after review (coordinator).** Fixed, test first, still uncommitted:
- **A11y:** ChoiceChips takes `labelledby`/`label`, `required` and `error` (tied by `aria-describedby`,
  `aria-invalid`), like TextField; the allergy question names its chips and carries its error.
- **One Level shape:** `LevelData {number, name}` (`App\Foundation\DataObjects`, beside `LevelChart`; Domains may not
  import each other) replaces `level` + `level_number` on ChildListRowData, ChildPortalData, AttendanceSquadData,
  DoorChildData and DoorCheckInData; the Families list and search/duplicate hits send children as `ListChildData
  {nickname, level}`. Pill takes a Level. Allowlist updated (a null child Level counts as its declared shape).
- **Coloured Level pills everywhere** — Families rows and search/duplicate cards too.
- **Kit:** `RowList` (card of padded rows; `href` makes the whole row a link ending in ›, `#after` outside it;
  `plain`) on Families, Children, guardians, history, door checked-in list, Attendance phone rows; `SectionHeading`
  for the 10 section headings; `EmailText` (breaks after `@` and in the name, never inside the domain);
  SelectField `placeholderDisabled` (the current Level/status is not pickable again, as before).
- "Remove this child" (and "Remove this parent or guardian") are danger buttons.
- **Look:** desktop menu items are tighter, so Sign out stays on the row; list names are bold with a › and no
  underline. Screenshots redone at 390/1280.
- Gates: `make check` green; `make e2e` 75 passed, 1 skipped.
