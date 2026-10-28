# 01 — Tokens, UI kit and app shell

Status: ready-for-agent
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
