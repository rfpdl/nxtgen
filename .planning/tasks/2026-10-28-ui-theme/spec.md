# The "Bold" theme — spec

Status: ready-for-agent
Decided: 2026-10-28 (Roy) — `docs/DECISIONS.md` 2026-10-28; reference: https://claude.ai/artifact/Jr2PHSMmx3VKXZcEmCVXw8 (option C).

## Why
The screens work but look plain and dated. Roy wants modern, sleek, easy, mobile- and elderly-friendly — done once
as a reusable theme, not page by page.

## Issues
| # | Issue | Blocked by |
|---|---|---|
| 01 | Tokens, UI kit and app shell | — |
| 02 | Every staff page on the kit | 01 |
| 03 | Every parent and public page on the kit | 01 |

## Test seams (Roy agreed the pattern on 2026-10-26)
1. **Vitest per kit component** — variants, disabled/loading, labels and errors wired for screen readers.
2. **The discipline gate** (component-discipline.test.ts) — no raw `<button>`, `<input>`, `<select>`, `<textarea>`
   or raw colours outside the kit; a page/organism using one fails.
3. **E2E unchanged in behaviour** — every journey stays green (test ids kept); plus a no-sideways-scroll check.
4. **The look step** — Claude screenshots the main screens on phone and desktop before each commit.
