# 01 — Tokens, UI kit and app shell

Status: resolved
Blocked by: —

## What
- Tokens in `resources/css/app.css` for the Bold look: Lexend (self-hosted via @fontsource), base text 18px,
  amber bar, the Level colours (+ soft backgrounds), pressable-button shadows, radii. Keep the 7:1 contrast rule
  for text on staff screens.
- Atoms: `AppButton` (primary green-pressable, secondary, quiet, danger; sizes; disabled/loading; as link),
  `TextField`, `SelectField`, `TextArea`, `CheckRow` (big tick row), `Card`, `Pill` (level 1–4, status, flag),
  `PageHeader`, `FlashBanner` restyled, `EmptyState`.
- App shell: amber top bar with the logo and page name; the menu as a big-button sheet on phones and a row on
  desktop; Password / Sign out inside it.
- Discipline gate extended (seam 2) with a known-gaps list of today's pages, emptied by issues 02 and 03.

## Done when (what you see)
The door page is already on the kit and looks like option C on an iPhone; the menu opens as a sheet; every other
page still works (old look until 02/03).

## Comments

**2026-10-28 — built (Claude).** `make check` and `make e2e` (73/73) green; looked at the door on an iPhone 14 and on
desktop, and at the menu sheet.

**Tokens** (`resources/css/app.css`): Lexend (`@fontsource-variable/lexend`, self-hosted; Inter dropped), 18px body
text, one orange focus ring, `bar`/`on-bar` (amber), `action`/`action-strong`/`action-edge` (the green, darkened to
7.6:1 with white — the chart's #2a9d3a is 3.5:1), `level-1..4` + `-soft` + `-ink` (each pair ≥7.7:1),
`shadow-press-action|danger|control`, `rounded-tick|control|card`. Every existing token is unchanged.

**Kit** (`components/atoms`, each with a Vitest file and a REGISTRY row): AppButton (primary/secondary/quiet/danger/
link; md/lg/icon; disabled; loading; `href` → Inertia Link, non-GET as a button), TextField, SelectField, TextArea
(label, hint and error wired with `aria-describedby`, `aria-invalid`; v-model; `class` sizes the field, other attrs
land on the control), CheckRow (role checkbox; whole-row with a label slot, or a 64px square named by `label`),
ChoiceChips (pressable chips, `aria-pressed`), Card, Pill (`level` 1–4, or danger/success/warning/info/neutral),
PageHeader, EmptyState, BrandMark. FlashBanner is restyled (solid green / red band); SignOutButton and SwitchLink are
AppButtons now.

**Shell**: `layout/AppShell` (amber bar: logo, page name, place; menu as a reka-ui Dialog sheet of big buttons under
768px — focus trapped, Esc and ✕ close — and a row in the bar above it; Switch and Sign out at the end). ChurchShell
is AppShell with the staff items (Password included); the bar names the page from the menu item the address is
under, or an optional `title`. Every test id is kept. No parent shell existed — Family/Show moves onto AppShell in 03.

**Door**: page and DoorFamilyCard, DoorChildRow, DoorCheckedInList, ChildHealthPanel are fully on the kit. The class
picker is chips (`door-class` is the group; each chip carries `data-value`). The tick is a CheckRow square beside
the name — not the whole row as in option C — because the row also opens the health details (now a Health button
beside the name). **Pending Roy's OK.** The checked-in list colours each Squad by Level, so `DoorCheckInData` now also sends `level`
(allowlist and door test updated).

**Gate** (component-discipline.test.ts, "the UI kit"): outside atoms, no raw `<button>`, `<input>` (file/hidden
allowed), `<select>` or `<textarea>`; the colour rule now also bans `*-white`/`*-black` and arbitrary colour values.
**KNOWN_GAPS: 27 files** (9 molecules, 11 organisms, 7 pages), may only shrink — issues 02 and 03 empty it.

**E2E selectors changed only where the control changed**: door + attendance pick 1PM by chip; lifeshaper's QR-poster
step opens the phone menu first (`tests/e2e/support/menu.ts`, a no-op on desktop).

**Use it**: `<AppButton>` for anything pressed, `<TextField v-model label>` etc. for inputs, `<Pill :level>` for a
Squad, `<Card>` for a tile, `<PageHeader>` for a page's h1; never a colour class — add a token or a variant.

**2026-10-28 — review fixes (Claude).** `make check` green (Vitest 301, Pest 1666); `make e2e` 75 passed, 1 skipped
(the phone-menu journey skips on desktop).
- **Contrast is a test now** (`resources/js/tests/contrast.test.ts`, which reads the tokens from app.css): text pairs
  ≥7:1; control edges and the focus ring ≥3:1. `border-strong` is darkened to clear 3:1 (the empty tick box and the
  kit's input edges use it). The new `focus` token is a dark ring; inside the amber bar or a flash band, the band's
  own `on-` colour is the ring (`focus-on-bar|action|danger`).
- **Token names**: `on-danger` (was `on-dark`) and `on-level-N` (was `level-N-ink`), with every use renamed. `action*`
  is the primary-button family; `primary*` is the teal accent.
- **Menu sheet**: phone or desktop is decided by CSS alone (`md:hidden` / `hidden md:flex`), so the first paint is
  already right. Vitest shows that Esc closes the sheet and gives focus back to Menu, and that focus can't leave the
  sheet. A phone e2e step reaches Password and Sign out through the sheet, Tabs round it and closes it with Esc.
  Fixed along the way: reka-ui's trap ignores Tab with Alt held, so Safari's Option+Tab walked out of the sheet;
  AppShell now wraps that case too. `support/menu.ts` waits for the bar before deciding.
- **Kit API**: ChoiceChips is a reka-ui radio group on `defineModel` (one Tab stop, arrow keys move between chips);
  the e2e tests pick 1PM by `role=radio`. A disabled or loading AppButton link doesn't navigate (`aria-disabled`, no
  href, or a disabled button for a POST). `type="submit"` with `href` logs a warning. A new `sm` size was added.
  SignOutButton and SwitchLink take `variant`/`size`/`block` as props.
- **Gate**: the rules are testable functions with example cases. A bound `:type` input counts as raw, a `>` inside a
  quoted attribute no longer cuts the tag short, and the colour rule also catches `bg-[var(--x)]`, `bg-(--x)`,
  `text-[color:…]` and `[color:…]`. KNOWN_GAPS is unchanged at 27.
- **Look**: the button reads "Tick to check in"; the search placeholder is "Search a name"; the list heading is just
  "Checked in" (the big count stays at the top); names are plain bold text with a small Health button beside them
  (`child-health-open`; `child-name` is kept for finding the row). On the door, Health is 56px tall like every
  button there, because of the door's hit-area rule.
