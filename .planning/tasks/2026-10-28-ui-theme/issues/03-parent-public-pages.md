# 03 — Every parent and public page on the kit

Status: resolved
Blocked by: 01

## Done when (what you see)
Welcome, register (+ thanks), sign-in, choose, password, the parent's family page (edit forms, photo): all Bold on
a phone; the gate's known-gaps list is empty.

## Comments

**2026-10-28 — Claude.** Done. Every parent and public page is on the kit and the Bold look:
- New `FamilyShell` (AppShell, `narrow`) for the family page: menu My family / Switch (when they also serve) / Sign
  out. AppShell without `items` is the bar alone (public, choose, nothing yet, backup password); `narrow` lines the
  bar, the flash and the page up in one column. BrandMark has a `large` size for the welcome page.
- Welcome: the logo, "Welcome to NxtGen", "Register your family" (primary) and "Sign in". Needs one new prop,
  `register_url` (the configured church's form) — DECISIONS 2026-10-28 (later).
- Login + PasswordLoginForm, ConfirmLink, Choose (each place one big button), NothingYet, Password, Register,
  RegisterThanks, Family/Show on PageHeader / Card / TextField / AppButton / RowList / SectionHeading.
- The gate's KNOWN_GAPS is empty and gone; `expectOnlyKnownGaps()` keeps the honesty check for the next guard.
  FormField (unused now) removed.
- e2e: test ids kept; the family page's Sign out is in the menu now, so `register.spec` signs out through
  `signOutFromMenu()` (support/menu.ts). Looked at every page at 390 and 1280 (no sideways scroll).
- Gates: `make check` and `make e2e` (76) green.

**2026-10-28 — Claude, after review.** `register_url` comes from GetChurchBySlugJob (lowercased, trimmed); null
when the church is missing or the setting empty, and Welcome then shows Sign in only. Tests: the Job (found,
capitals, missing/empty) and the Feature (the returned URL GETs 200; capitals; missing church; empty setting); e2e
clicks "Register your family" to the form. Family page heading is "The {last name} family" (the bar keeps "My
family"). FamilyShell test resets `can_switch`. e2e asserts the exact "Welcome to NxtGen" heading after sign-out.
