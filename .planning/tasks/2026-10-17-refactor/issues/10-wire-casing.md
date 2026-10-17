# 10 — snake_case across the wire, camelCase inside PHP

Status: resolved
Blocked by: 09

DECISIONS 2026-10-17 (later — Roy's answers).

## What
- Every Inertia page prop name is snake_case (`canSetLevel` → `can_set_level`, `grantOptions` → `grant_options`,
  shared `churchNav` → `church_nav`, …); Vue `defineProps` declare the snake_case names; switch off
  `vue/prop-name-casing` in eslint with a one-line reason.
- Internal `#[NonClientFacing]` DTOs (PHP → PHP hand-offs) go back to camelCase properties.
- Response DTOs, Inputs and generated TS stay snake_case. Screens unchanged.

## Done when
No camelCase key in any Inertia response (an arch or Pest guard proves it); no snake_case property on a
NonClientFacing DTO (guard); `make check` + `make e2e` green.

## Comments

- **Done.** Page props renamed: `hasPassword` → `has_password`, `canSetLevel` → `can_set_level`,
  `grantOptions` → `grant_options`, `registerUrl` → `register_url`; shared `churchNav` → `church_nav`
  (HandleInertiaRequests, `SharedProps`, ChurchShell). Pages declare the snake_case names; a child component's
  own props (e.g. `ChildAdminControls`' `canSetLevel`) stay camelCase — they never cross the wire.
  `vue/prop-name-casing` is off in eslint.config.js with a one-line reason.
- The 14 `#[NonClientFacing]` DTOs (Family registration/filters, Registry import rows/results) now have camelCase
  properties; every caller was found by PHPStan L10 (unknown named argument / undefined property) and fixed.
- **Guards.** `tests/Feature/WireCasingTest.php` asks the app, not the source: every GET route the services serve,
  opened by every AccessWorld actor, every key at every depth of the props (shared and lazy included) — no
  camelCase. Its guard-on-the-guard: the set of page components it saw must equal every component a Feature
  renders, so an unreachable page fails rather than going unchecked. `DataObjectTest` gains two rules: a
  NonClientFacing DTO has no snake_case property; a client-facing DTO or an Input has no camelCase property.
- Response allowlists, SharedPropsTest and the Feature tests now pin the snake_case names.
- `tests/e2e/staff/satellite-admin.spec.ts` story#77 read the role options once with `allTextContents()`, which
  raced the render while `make dev`'s Vite server was up (`public/hot` makes e2e load dev assets); it now uses a
  retrying `toHaveText`. Screens unchanged; `typescript:transform` produced no change (NonClientFacing DTOs are not
  generated).
